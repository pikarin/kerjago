import { usePage } from '@inertiajs/vue3';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import type {
    CapabilityDecision,
    EmployerCapabilityName,
    VerificationState,
} from '@/types/capabilities';

const DENIED_WITHOUT_PROFILE: CapabilityDecision = {
    allowed: false,
    reason: null,
};

export type UseCapabilitiesReturn = {
    can: (capability: EmployerCapabilityName) => boolean;
    decision: (capability: EmployerCapabilityName) => CapabilityDecision;
    verification: ComputedRef<VerificationState | null>;
};

/**
 * Reads the capability map the server shares on every page.
 *
 * Components ask "may they do this?" and never "are they verified?" — the same
 * rule the backend follows, and for the same reason: when a package can also
 * unlock a feature, nothing here needs finding again.
 *
 * Anyone without an employer profile — guests, jobseekers, staff — gets a
 * denial with no reason, because none of these gates are about them. Those
 * surfaces are not rendered for them in the first place; failing closed is
 * simply the safer default if one ever is.
 */
export function useCapabilities(): UseCapabilitiesReturn {
    const page = usePage();

    const decision = (capability: EmployerCapabilityName): CapabilityDecision =>
        page.props.capabilities?.[capability] ?? DENIED_WITHOUT_PROFILE;

    return {
        can: (capability) => decision(capability).allowed,
        decision,
        verification: computed(() => page.props.verification),
    };
}
