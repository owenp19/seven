/**
 * Payload of the marketplace provider (business behind a tour, restaurant
 * or inn). Mirror of the backend ProviderResource.
 */
export interface ProviderPayload {
  id: number;
  business_name?: string | null;
  owner_name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  logo?: string | null;
  type?: string | null;
  status?: string | null;
  verification_status?: string | null;
}