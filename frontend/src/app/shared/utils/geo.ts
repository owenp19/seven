import { GeoPosition } from '../../core/services/native.service';

/** Puntos conocidos de San Andrés para estimar distancia/tiempo desde el usuario. */
interface IslandSpot {
  name: string;
  match: string[];
  lat: number;
  lng: number;
}

const ISLAND_SPOTS: IslandSpot[] = [
  { name: 'Johnny Cay', match: ['johnny'], lat: 12.556, lng: -81.702 },
  { name: 'Acuario Natural y Haynes Cay', match: ['acuario', 'haynes', 'rose'], lat: 12.565, lng: -81.701 },
  { name: 'West View', match: ['west view'], lat: 12.481, lng: -81.759 },
  { name: 'La Piscinita', match: ['piscinita'], lat: 12.477, lng: -81.775 },
  { name: 'El Hoyo Soplador', match: ['hoyo soplador', 'hoyo'], lat: 12.472, lng: -81.732 },
  { name: 'Casa Museo Isleña', match: ['casa museo'], lat: 12.516, lng: -81.727 },
  { name: 'Muelle Principal', match: ['muelle principal', 'muelle'], lat: 12.562, lng: -81.708 },
  { name: 'San Luis', match: ['san luis'], lat: 12.513, lng: -81.692 },
  { name: 'La Regatta', match: ['regatta'], lat: 12.535, lng: -81.711 },
  { name: 'Bahía de San Andrés', match: ['bahía', 'bahia'], lat: 12.556, lng: -81.708 },
];

export interface NearbyEstimate {
  distanceKm: number;
  travelMinutes: number;
  transport: string;
  display: string;
}

export interface ResolvedSpot {
  name: string;
  lat: number;
  lng: number;
}

/** Resuelve un texto de ubicación ("Muelle Principal, San Andrés") a coordenadas. */
export function resolveSpot(location: string): ResolvedSpot | null {
  const needle = location.toLowerCase();
  const spot = ISLAND_SPOTS.find((s) => s.match.some((m) => needle.includes(m)));
  if (!spot) {
    return null;
  }
  return { name: spot.name, lat: spot.lat, lng: spot.lng };
}

/** Distancia aproximada (km) en línea recta (Haversine). */
export function distanceKm(a: GeoPosition, b: { lat: number; lng: number }): number {
  const R = 6371;
  const dLat = ((b.lat - a.latitude) * Math.PI) / 180;
  const dLng = ((b.lng - a.longitude) * Math.PI) / 180;
  const la1 = (a.latitude * Math.PI) / 180;
  const la2 = (b.lat * Math.PI) / 180;

  const h =
    Math.sin(dLat / 2) ** 2 + Math.cos(la1) * Math.cos(la2) * Math.sin(dLng / 2) ** 2;
  const d = 2 * R * Math.asin(Math.sqrt(h));
  return Math.round(d * 10) / 10;
}

const TRANSPORTS = [
  { min: 2, label: 'A pie', kmh: 4.5 },
  { min: 99, label: 'En lancha o carro', kmh: 28 },
] as const;

/** Estima distancia + tiempo puerta a puerta según el radio (km). */
export function estimateNearby(origin: GeoPosition, spot: ResolvedSpot): NearbyEstimate {
  const distanceKmValue = distanceKm(origin, spot);
  const travel = TRANSPORTS.find((t) => distanceKmValue <= t.min) ?? TRANSPORTS[TRANSPORTS.length - 1];
  const minutes = Math.max(travel.min === 2 ? 3 : 6, Math.round((distanceKmValue / travel.kmh) * 60));

  return {
    distanceKm: distanceKmValue,
    travelMinutes: minutes,
    transport: travel.label,
    display: `A unos ${distanceKmValue} km · ~${minutes} min ${travel.label.toLowerCase()} aprox.`,
  };
}

/** URL universal del mapa de Google para una coordenada o un texto. */
export function mapsUrl(target: { lat: number; lng: number } | string): string {
  if (typeof target === 'string') {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(target)}`;
  }
  return `https://www.google.com/maps/search/?api=1&query=${target.lat},${target.lng}`;
}