<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use App\Models\EmployerProfile;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Realistic roles so seeded data exercises semantic (embedding-based)
     * search meaningfully — lorem ipsum descriptions embed as noise.
     *
     * @var list<array{title: string, description: string, skills: list<string>}>
     */
    private const array ROLES = [
        ['title' => 'Senior Laravel Developer', 'description' => 'Design and build backend services for our recruitment marketplace using Laravel, PostgreSQL and Redis. You will own features end to end, from database schema to API, write automated tests with Pest, and mentor junior engineers on code review.', 'skills' => ['PHP', 'Laravel', 'PostgreSQL', 'Redis']],
        ['title' => 'Frontend Engineer (Vue.js)', 'description' => 'Build responsive single-page application interfaces with Vue 3, TypeScript and Tailwind CSS. You will translate Figma designs into accessible components, optimise page load performance, and collaborate closely with backend engineers on API contracts.', 'skills' => ['Vue.js', 'TypeScript', 'Tailwind CSS']],
        ['title' => 'Full Stack Web Developer', 'description' => 'Ship features across the whole stack: PHP and Laravel on the server, Vue and Inertia on the client. Ideal for an engineer who enjoys owning a product area, from database migrations to pixel-perfect UI.', 'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL']],
        ['title' => 'Mobile Engineer (Flutter)', 'description' => 'Develop and maintain our cross-platform mobile app for jobseekers using Flutter and Dart. Work on push notifications, offline caching and smooth animations across Android and iOS.', 'skills' => ['Flutter', 'Dart', 'Firebase']],
        ['title' => 'Data Analyst', 'description' => 'Turn hiring-funnel data into insight. Build dashboards in Looker, write SQL against our warehouse, run A/B test analysis and present findings that shape product decisions.', 'skills' => ['SQL', 'Python', 'Looker']],
        ['title' => 'Machine Learning Engineer', 'description' => 'Improve job-matching relevance with embedding models and learning-to-rank. You will train and deploy models, run offline evaluations, and ship inference services in Python.', 'skills' => ['Python', 'PyTorch', 'MLOps']],
        ['title' => 'DevOps Engineer', 'description' => 'Own our cloud infrastructure: Kubernetes clusters, CI/CD pipelines, observability and cost. Automate everything with Terraform and keep production reliable during rapid growth.', 'skills' => ['Kubernetes', 'Terraform', 'AWS', 'Docker']],
        ['title' => 'QA Automation Engineer', 'description' => 'Build and maintain end-to-end test suites with Playwright, define regression strategy for web and mobile releases, and champion quality across squads.', 'skills' => ['Playwright', 'TypeScript', 'CI/CD']],
        ['title' => 'Product Designer', 'description' => 'Design intuitive flows for jobseekers and employers. You will run user research, produce high-fidelity prototypes in Figma, and maintain our design system with engineers.', 'skills' => ['Figma', 'UX Research', 'Prototyping']],
        ['title' => 'Product Manager', 'description' => 'Own the roadmap for our employer-facing hiring tools. Talk to customers weekly, write crisp specs, prioritise ruthlessly and partner with design and engineering to ship outcomes.', 'skills' => ['Product Strategy', 'Analytics', 'Agile']],
        ['title' => 'Digital Marketing Specialist', 'description' => 'Grow candidate acquisition through SEO, paid social and email campaigns. You will manage budgets, run experiments and report on funnel conversion.', 'skills' => ['SEO', 'Google Ads', 'Copywriting']],
        ['title' => 'Content Writer', 'description' => 'Write career advice articles, employer branding stories and localized landing pages. Strong command of English and Bahasa Indonesia, with an eye for SEO.', 'skills' => ['Copywriting', 'SEO', 'Editing']],
        ['title' => 'Customer Success Manager', 'description' => 'Help employers get value from our platform: onboarding, training, renewal conversations and voice-of-customer feedback to the product team.', 'skills' => ['Account Management', 'CRM', 'Communication']],
        ['title' => 'Sales Executive (B2B)', 'description' => 'Prospect and close small and medium businesses that need to hire fast. You will run demos, negotiate contracts and consistently beat quota.', 'skills' => ['B2B Sales', 'Negotiation', 'CRM']],
        ['title' => 'HR Generalist', 'description' => 'Run day-to-day people operations: recruitment coordination, onboarding, payroll inputs and employee engagement programs for a fast-growing team.', 'skills' => ['Recruitment', 'Payroll', 'Employee Relations']],
        ['title' => 'Finance Analyst', 'description' => 'Own monthly management reporting, budgeting and cash-flow forecasts. Partner with department leads on spend and support fundraising due diligence.', 'skills' => ['Financial Modelling', 'Excel', 'Reporting']],
        ['title' => 'Operations Coordinator', 'description' => 'Keep the office and remote teams running: vendor management, scheduling, procurement and process documentation.', 'skills' => ['Scheduling', 'Procurement', 'Documentation']],
        ['title' => 'Graphic Designer', 'description' => 'Produce social media visuals, campaign assets and pitch decks. You will keep our brand consistent across every channel.', 'skills' => ['Figma', 'Illustrator', 'Branding']],
        ['title' => 'Backend Engineer (Go)', 'description' => 'Build high-throughput microservices in Go for search and notifications. Care about latency budgets, clean interfaces and production observability.', 'skills' => ['Go', 'gRPC', 'PostgreSQL']],
        ['title' => 'Security Engineer', 'description' => 'Harden our platform: threat modelling, dependency scanning, incident response and security reviews of new features handling personal data.', 'skills' => ['Application Security', 'AWS', 'Incident Response']],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array<string, array{Currency, int, int}> $markets */
        $markets = [
            'ID' => [Currency::Idr, 5_000_000, 60_000_000],
            'SG' => [Currency::Sgd, 3_000, 18_000],
            'MY' => [Currency::Myr, 3_000, 25_000],
            'PH' => [Currency::Php, 25_000, 250_000],
            'VN' => [Currency::Vnd, 10_000_000, 120_000_000],
            'TH' => [Currency::Thb, 25_000, 250_000],
        ];

        $country = array_rand($markets);
        [$currency, $floor, $ceiling] = $markets[$country];

        $salaryMin = fake()->numberBetween($floor, intdiv($ceiling, 2));
        $role = self::ROLES[array_rand(self::ROLES)];

        return [
            'employer_profile_id' => EmployerProfile::factory(),
            'title' => $role['title'],
            'description' => $role['description'],
            'skills' => $role['skills'],
            'location_country' => $country,
            'location_city' => fake()->city(),
            'salary_min' => $salaryMin,
            'salary_max' => fake()->numberBetween($salaryMin, $ceiling),
            'currency' => $currency,
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'work_arrangement' => fake()->randomElement(WorkArrangement::cases()),
            'experience_level' => fake()->randomElement(ExperienceLevel::cases()),
            'education_level' => fake()->randomElement(EducationLevel::cases()),
            'status' => JobStatus::Active,
        ];
    }

    /**
     * Indicate that the job is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Draft,
        ]);
    }

    /**
     * A job posted before the facet fields existed.
     */
    public function legacy(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => null,
            'work_arrangement' => null,
            'experience_level' => null,
            'education_level' => null,
        ]);
    }

    /**
     * Indicate that the job is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Closed,
        ]);
    }
}
