<?php

namespace App\Admingo\Resources\EmployerProfiles;

use App\Actions\Verification\UnverifyEmployer;
use App\Actions\Verification\VerifyEmployer;
use App\Admingo\Resources\EmployerProfiles\Pages\ListEmployerProfiles;
use App\Admingo\Resources\EmployerProfiles\Pages\ViewEmployerProfile;
use App\Enums\JobStatus;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;

/**
 * Companies, and the verification decision staff make about them.
 *
 * Every complete profile is browsable, but the ones that asked sort first and
 * drive the navigation badge: posting a job is not the only reason to want
 * verification, and an employer drawn in by Talent Search may never post
 * anything at all.
 *
 * Both decisions run through the domain Actions rather than writing columns
 * here. Nothing in the domain may depend on Admingo, so the arrow only ever
 * points this way.
 */
class EmployerProfileResource extends Resource
{
    protected static ?string $model = EmployerProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'company_name';

    protected static ?string $navigationLabel = 'Companies';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company')->schema([
                TextEntry::make('company_name'),
                TextEntry::make('industry'),
                TextEntry::make('city'),
                TextEntry::make('country'),
                TextEntry::make('website')
                    ->url(fn (?string $state): ?string => $state)
                    ->placeholder('—'),
                TextEntry::make('user.email')
                    ->label('Account'),
            ])->columns(3),

            Section::make('Verification')->schema([
                TextEntry::make('verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->placeholder('Not verified'),
                TextEntry::make('verifiedBy.email')
                    ->label('Verified by')
                    ->placeholder('—'),
                TextEntry::make('verification_requested_at')
                    ->label('Requested')
                    ->dateTime()
                    ->placeholder('Never asked'),
            ])->columns(3),

            Section::make('History')
                ->description('Every decision ever made about this company. Reasons here are internal.')
                ->schema([
                    TextEntry::make('verificationEvents')
                        ->hiddenLabel()
                        ->state(fn (EmployerProfile $record): array => $record
                            ->verificationEvents()
                            ->with('actor:id,email')
                            ->latest()
                            ->get()
                            ->map(fn (EmployerVerificationEvent $event): string => sprintf(
                                '%s — %s by %s%s',
                                $event->created_at?->toDayDateTimeString() ?? 'Unknown time',
                                $event->decision->value,
                                // Null for anything the platform decided on its
                                // own, and for a staff account since deleted —
                                // the history outlives the person.
                                $event->actor->email ?? 'the system',
                                $event->reason === null ? '' : ' — '.$event->reason,
                            ))
                            ->all())
                        ->listWithLineBreaks()
                        ->placeholder('No decisions yet'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->columns([
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Account')
                    ->searchable(),
                TextColumn::make('verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('verification_requested_at')
                    ->label('Waiting since')
                    ->since()
                    ->placeholder('Not asked')
                    ->sortable(),
                TextColumn::make('jobs_pending_count')
                    ->counts([
                        'jobs' => fn (Builder $query) => $query->where('status', JobStatus::Pending),
                    ])
                    ->label('Ads waiting'),
            ])
            ->filters([
                Filter::make('unverified')
                    ->label('Not yet verified')
                    ->query(fn (Builder $query) => $query->whereNull('verified_at'))
                    ->default(),
                Filter::make('requested')
                    ->label('Asked to be verified')
                    ->query(fn (Builder $query) => $query->whereNotNull('verification_requested_at')),
            ])
            // Requesters first, oldest ask at the top: the queue is worked in
            // the order people started waiting.
            ->defaultSort('verification_requested_at', 'asc')
            ->recordActions([
                ViewAction::make(),
                self::verifyAction(),
                self::publishProgressAction(),
                self::unverifyAction(),
            ]);
    }

    /**
     * Mark a company verified, then hand the staff member a live view of the
     * ads going up.
     *
     * The modal is informational, not blocking: closing it does not cancel
     * anything, and the Jobs queue shows the same run draining. Staff who need
     * to know it finished can stay; staff who do not can leave.
     */
    public static function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verify')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (EmployerProfile $record): bool => ! $record->isVerified())
            ->schema([
                Textarea::make('employer_message')
                    ->label('Message to the company (optional)')
                    ->helperText('Included in the email we send them.')
                    ->rows(3),
            ])
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, EmployerProfile $record, BasePage $livewire): void {
                    /** @var User $staff */
                    $staff = auth('admingo')->user();

                    app(VerifyEmployer::class)->handle(
                        $record,
                        $staff,
                        employerMessage: self::text($data['employer_message'] ?? null),
                    );

                    Notification::make()
                        ->success()
                        ->title($record->company_name.' is verified')
                        ->body('Any ads they had waiting are being published now.')
                        ->send();

                    if ($record->publish_batch_id === null) {
                        return;
                    }

                    // Straight from the confirmation into the progress view, so
                    // staff who want to see the run finish do not have to know to
                    // go looking for it.
                    //
                    // The context differs by where this was clicked: from the table
                    // the action is resolved off the table and needs the row key,
                    // while on the record's own page it resolves from the header
                    // actions and already knows its record.
                    $livewire->replaceMountedAction('publishProgress', context: $livewire instanceof HasTable
                        ? ['table' => true, 'recordKey' => $record->getKey()]
                        : []);
                },
            )
            ->modalSubmitActionLabel('Verify company');
    }

    /**
     * Live progress of the publish run, for staff who want to wait it out.
     *
     * Reachable on its own as well as straight after verifying: the run
     * survives the modal being closed, so there has to be a way back to it.
     */
    public static function publishProgressAction(): Action
    {
        return Action::make('publishProgress')
            ->label('Publishing')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('info')
            ->visible(fn (EmployerProfile $record): bool => $record->publish_batch_id !== null)
            ->modalHeading('Publishing waiting ads')
            ->modalContent(fn (EmployerProfile $record): View => view('admingo.publish-progress', [
                'batch' => Bus::findBatch((string) $record->publish_batch_id),
                'company' => $record->company_name,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * Withdraw a company's standing. Requires an internal reason, because the
     * audit row is worthless without one — and takes their live ads down.
     */
    public static function unverifyAction(): Action
    {
        return Action::make('unverify')
            ->label('Unverify')
            ->icon(Heroicon::OutlinedShieldExclamation)
            ->color('danger')
            ->visible(fn (EmployerProfile $record): bool => $record->isVerified())
            ->schema([
                Textarea::make('reason')
                    ->label('Internal reason')
                    ->helperText('Never shown to the company. Say what actually happened.')
                    ->required()
                    ->rows(3),
                Textarea::make('employer_message')
                    ->label('Message to the company (optional)')
                    ->helperText('The only part quoted in their email.')
                    ->rows(3),
            ])
            ->requiresConfirmation()
            ->modalDescription('Their live job ads come down and go back to waiting. Applications and unlocks already issued are untouched.')
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, EmployerProfile $record): void {
                    /** @var User $staff */
                    $staff = auth('admingo')->user();

                    app(UnverifyEmployer::class)->handle(
                        $record,
                        self::text($data['reason'] ?? null) ?? '',
                        $staff,
                        employerMessage: self::text($data['employer_message'] ?? null),
                    );

                    Notification::make()
                        ->warning()
                        ->title('Verification withdrawn for '.$record->company_name)
                        ->send();
                },
            )
            ->modalSubmitActionLabel('Withdraw verification');
    }

    /**
     * A textarea's submitted value, or null when it was left empty.
     *
     * Filament hands the action closure untyped form state; narrowing it here
     * keeps the domain Actions' string signatures honest instead of casting
     * mixed at the call site.
     */
    private static function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }

    /**
     * How many companies are waiting on an answer they asked for. Companies
     * that never asked are still listed, but they are not a queue.
     */
    public static function getNavigationBadge(): ?string
    {
        $waiting = EmployerProfile::query()
            ->whereNull('verified_at')
            ->whereNotNull('verification_requested_at')
            ->count();

        return $waiting === 0 ? null : (string) $waiting;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployerProfiles::route('/'),
            'view' => ViewEmployerProfile::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
