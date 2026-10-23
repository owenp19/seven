import type { TranslationKey } from '../../core/services/translation.service';
import type { AssistantItemType, AssistantPriceUnit } from '../../models/assistant.model';

/**
 * Etiquetas del asistente.
 *
 * Traduce el tipo de servicio y la unidad de precio que devuelve el backend a
 * claves de traducción, para que los tres idiomas cubran cualquier vertical
 * nueva sin tocar las tarjetas.
 */
const TYPE_KEYS: Record<AssistantItemType, TranslationKey> = {
  tour: 'assistant.type.tour',
  experience: 'assistant.type.experience',
  inn: 'assistant.type.inn',
  accommodation: 'assistant.type.accommodation',
  restaurant: 'assistant.type.restaurant',
  bakery: 'assistant.type.bakery',
  business: 'assistant.type.business',
};

export function assistantTypeKey(type: AssistantItemType): TranslationKey {
  return TYPE_KEYS[type];
}

/** Prefijo del precio: "Por noche" solo cuando el precio es por noche. */
export function assistantPriceLabelKey(unit: AssistantPriceUnit | null): TranslationKey {
  return unit === 'per_night' ? 'assistant.perNight' : 'assistant.from';
}
