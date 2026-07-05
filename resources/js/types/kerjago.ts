export type JobStatus = 'draft' | 'active' | 'closed';

export type ApplicationStatus =
    'submitted' | 'reviewed' | 'shortlisted' | 'rejected';

export type CountryCode = 'ID' | 'SG' | 'MY' | 'PH' | 'VN' | 'TH';

export type CurrencyCode = 'IDR' | 'SGD' | 'MYR' | 'PHP' | 'VND' | 'THB';

export type EmploymentType =
    'full_time' | 'part_time' | 'contract' | 'internship' | 'freelance';

export type WorkArrangement = 'onsite' | 'hybrid' | 'remote';

export type ExperienceLevel = 'entry' | 'mid' | 'senior' | 'lead';

export type EducationLevel =
    'none' | 'high_school' | 'diploma' | 'bachelor' | 'master' | 'doctorate';

export type FacetOption = { value: string; label: string };

export type FacetCount = { value: string; count: number };

export type Facets = Record<string, FacetCount[]>;

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};

export type JobSummary = {
    id: string;
    title: string;
    company_name: string;
    location_city: string;
    location_country: CountryCode;
    salary_min: number;
    salary_max: number;
    currency: CurrencyCode;
    employment_type: EmploymentType | null;
    work_arrangement: WorkArrangement | null;
    experience_level: ExperienceLevel | null;
    skills: string[];
    posted_at: string | null;
};

export type JobDetail = {
    id: string;
    title: string;
    description: string;
    skills: string[];
    location_city: string;
    location_country: CountryCode;
    salary_min: number;
    salary_max: number;
    currency: CurrencyCode;
    posted_at: string | null;
    company: {
        name: string;
        industry: string;
        city: string;
        country: CountryCode;
        website: string | null;
    };
};

export type TalentProfile = {
    id: string;
    full_name: string;
    current_title: string;
    skills: string[];
    experience_years: number;
    country: CountryCode;
    city: string;
    phone?: string | null;
};

export const COUNTRY_LABELS: Record<CountryCode, string> = {
    ID: 'Indonesia',
    SG: 'Singapore',
    MY: 'Malaysia',
    PH: 'Philippines',
    VN: 'Vietnam',
    TH: 'Thailand',
};

export function countryLabel(code: string): string {
    return COUNTRY_LABELS[code as CountryCode] ?? code;
}

export function formatSalaryRange(
    min: number,
    max: number,
    currency: string,
): string {
    const format = new Intl.NumberFormat('en', {
        notation: 'compact',
        maximumFractionDigits: 1,
    });

    return `${currency} ${format.format(min)} – ${format.format(max)}`;
}
