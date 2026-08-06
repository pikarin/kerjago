export type JobStatus = 'draft' | 'active' | 'closed' | 'expired';

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
    employment_type: EmploymentType | null;
    work_arrangement: WorkArrangement | null;
    experience_level: ExperienceLevel | null;
    education_level: EducationLevel | null;
    posted_at: string | null;
    company: {
        name: string;
        industry: string;
        city: string;
        country: CountryCode;
        website: string | null;
    };
};

export type Availability =
    'immediately' | 'two_weeks' | 'one_month' | 'two_months_plus';

export type Gender = 'male' | 'female' | 'prefer_not_to_say';

export type LanguageCode = 'id' | 'en' | 'ms' | 'zh' | 'th' | 'vi' | 'tl';

export type LanguageProficiency = 'basic' | 'good' | 'fluent' | 'native';

export type SalaryPeriod = 'monthly' | 'yearly' | 'hourly';

export type SpokenLanguage = {
    id?: string;
    language: LanguageCode;
    proficiency: LanguageProficiency;
};

export type TalentProfile = {
    id: string;
    // True unless the viewing employer holds an active Candidate Unlock. The
    // strings below are already masked server-side when it is — there is no raw
    // value on the client to reveal (ADR 0013).
    is_locked: boolean;
    full_name: string;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    current_title: string;
    current_company: string | null;
    preferred_job_title: string | null;
    summary: string | null;
    skills: string[];
    experience_years: number;
    country: CountryCode;
    state: string | null;
    city: string;
    preferred_country: CountryCode | null;
    preferred_state: string | null;
    preferred_city: string | null;
    availability: Availability | null;
    gender: Gender | null;
    // Derived from the birth date server-side; the birth date is never shared.
    age: number | null;
    education_level: EducationLevel | null;
    expected_salary_min: number | null;
    expected_salary_max: number | null;
    expected_salary_currency: CurrencyCode | null;
    expected_salary_period: SalaryPeriod | null;
    profile_views_count: number;
    resume_downloads_count: number;
    employer_actions_count: number;
    last_active_at: string | null;
};

// Applicant cards carry contact details only for the first ten applicants to a
// job; everyone after them arrives masked, exactly as in talent search.
export type ApplicantProfile = {
    id: string;
    is_locked: boolean;
    full_name: string;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    current_title: string;
    skills: string[];
    experience_years: number;
    country: CountryCode;
    city: string;
};

// A locked applicant's thread, rendered from application data rather than from
// chat: no unread count, no timestamp, no preview.
export type LockedApplicantTeaser = {
    application_id: string;
    job_id: string;
    job_title: string;
    display_name: string | null;
    current_title: string;
};

export type WorkExperienceItem = {
    id: string;
    job_title: string;
    company_name: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
    is_current: boolean;
};

export type EducationItem = {
    id: string;
    institution: string;
    field_of_study: string | null;
    level: EducationLevel | null;
    start_date: string | null;
    end_date: string | null;
};

export type TalentSummary = TalentProfile & {
    languages: SpokenLanguage[];
};

export type TalentDetail = TalentProfile & {
    languages: SpokenLanguage[];
    work_experiences: WorkExperienceItem[];
    educations: EducationItem[];
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
