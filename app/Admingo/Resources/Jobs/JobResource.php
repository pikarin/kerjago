<?php

namespace App\Admingo\Resources\Jobs;

use App\Admingo\Resources\Jobs\Pages\ListJobs;
use App\Admingo\Resources\Jobs\Pages\ViewJob;
use App\Enums\JobStatus;
use App\Models\Job;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Every job ad, read-only, defaulting to the pending queue.
 *
 * The queue staff actually work is cross-employer — "what is waiting on us
 * today?", not "what is this one company waiting on" — so it lives here rather
 * than nested under each employer. It doubles as the progress view while a
 * verification's publish batch drains: each ad flips Pending to Active as its
 * queued job lands.
 *
 * **Read-only on purpose.** No create, no edit, no delete. Staff rewriting an
 * employer's ad behind their back is a surprise with no notification and no
 * audit trail, and the one mutation that is wanted — publishing on verification
 * — happens through the domain Action, not from here.
 */
class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Jobs';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ad')->schema([
                TextEntry::make('title'),
                TextEntry::make('employerProfile.company_name')
                    ->label('Company'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('description')
                    ->columnSpanFull(),
            ])->columns(3),

            Section::make('Lifecycle')->schema([
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('Never published'),
                TextEntry::make('expires_at')
                    ->dateTime()
                    // A pending ad carries no clock: the window is stamped when
                    // it goes live, not when it was written.
                    ->placeholder('No window running'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('employerProfile.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('applications_count')
                    ->counts('applications')
                    ->label('Applicants'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(JobStatus::class)
                    // The work queue is the default view; staff arriving here
                    // are almost always answering "what is waiting on us?".
                    ->default(JobStatus::Pending->value),
            ])
            ->defaultSort('created_at', 'desc')
            // Polled so the pending queue drains in front of whoever just
            // verified a company, rather than needing a refresh to prove
            // anything happened.
            ->poll('5s')
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * How many ads are waiting on a decision. The one number that tells staff
     * whether there is work to do without opening anything.
     */
    public static function getNavigationBadge(): ?string
    {
        $pending = Job::query()->where('status', JobStatus::Pending)->count();

        return $pending === 0 ? null : (string) $pending;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobs::route('/'),
            'view' => ViewJob::route('/{record}'),
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
