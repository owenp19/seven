import {
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  OnInit,
  computed,
  inject,
  signal,
  viewChild,
} from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';

import { Location } from '@angular/common';

import { AssistantItemType, AssistantPackage, AssistantRecommendation } from '../../models/assistant.model';
import { AssistantService } from '../../services/assistant.service';
import { HapticsService } from '../../core/services/haptics.service';
import { TranslationService, type TranslationKey } from '../../core/services/translation.service';
import { formatCurrency } from '../../utils/formatters';
import { CurrencyPipe } from '../../shared/pipes/currency/currency.pipe';
import { SevenAssistantHeaderComponent } from '../../shared/components/seven-assistant-header/seven-assistant-header.component';
import {
  SevenQuickIntentComponent,
  type QuickIntent,
} from '../../shared/components/seven-quick-intent/seven-quick-intent.component';
import { SevenThinkingComponent } from '../../shared/components/seven-thinking/seven-thinking.component';
import { SevenComparisonCardComponent } from '../../shared/components/seven-comparison-card/seven-comparison-card.component';
import { SevenAssistantRecommendationCardComponent } from '../../shared/components/seven-assistant-recommendation-card/seven-assistant-recommendation-card.component';
import { SevenPackageCardComponent } from '../../shared/components/seven-package-card/seven-package-card.component';
import { TranslatePipe } from '../../shared/pipes/translate/translate.pipe';

/** Un turno del hilo de conversación (usuario o asistente). */
interface AssistantChatMessage {
  id: number;
  role: 'user' | 'assistant';
  text: string;
  recommendations: AssistantRecommendation[];
  packages: AssistantPackage[];
  /** `true` cuando el turno es una degradación por fallo de red/servidor. */
  error: boolean;
}

/** Atajo de catálogo del bloque de alternativas. */
interface FallbackTile {
  icon: string;
  labelKey: TranslationKey;
  route: string;
}

/**
 * AssistantPage
 *
 * Experiencia del concierge SEVEN. La primera pantalla NO es un chat vacío:
 * propone un descubrimiento guiado (saludo + intenciones rápidas + inspiración)
 * y, al conversar, mezcla texto editorial con componentes visuales reales
 * (tarjetas, comparación, itinerario y medidor de presupuesto). Todo lo que se
 * ve viene de la respuesta del servidor: no se inventan precios ni destinos.
 */
@Component({
  selector: 'app-assistant',
  templateUrl: './assistant.page.html',
  styleUrls: ['./assistant.page.scss'],
  standalone: true,
  imports: [
    IonContent,
    IonIcon,
    TranslatePipe,
    SevenAssistantHeaderComponent,
    SevenQuickIntentComponent,
    SevenThinkingComponent,
    SevenComparisonCardComponent,
    SevenAssistantRecommendationCardComponent,
    SevenPackageCardComponent,
    CurrencyPipe,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AssistantPage implements OnInit {
  private readonly assistant = inject(AssistantService);
  private readonly haptics = inject(HapticsService);
  private readonly i18n = inject(TranslationService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly location = inject(Location);

  private readonly scroll = viewChild(IonContent);
  private readonly composer = viewChild<ElementRef<HTMLTextAreaElement>>('composerInput');

  readonly messages = signal<AssistantChatMessage[]>([]);
  readonly sending = signal(false);
  readonly draft = signal('');

  /** Máximo de caracteres que acepta el backend; se replica en el input. */
  readonly maxChars = 1000;

  /** Descubrimiento: se muestra solo en el estado inicial. */
  readonly showDiscovery = computed(() => this.messages().length === 0 && !this.sending());

  /** ¿Hay algo que reiniciar? Controla el botón de nueva conversación. */
  readonly canReset = computed(() => this.messages().length > 0);

  /** Id del último turno del asistente (para acciones de refinado). */
  readonly lastAssistantId = computed(() => {
    const list = this.messages();
    for (let i = list.length - 1; i >= 0; i -= 1) {
      if (list[i].role === 'assistant') {
        return list[i].id;
      }
    }
    return -1;
  });

  /** Último plan disponible, para el panel lateral en escritorio. */
  readonly latestPackage = computed<AssistantPackage | null>(() => {
    const list = this.messages();
    for (let i = list.length - 1; i >= 0; i -= 1) {
      const packs = list[i].packages;
      if (packs.length > 0) {
        return packs[0];
      }
    }
    return null;
  });

  readonly intents: QuickIntent[] = [
    { icon: 'cash-outline', labelKey: 'assistant.intentBudget', promptKey: 'assistant.promptBudget' },
    { icon: 'restaurant-outline', labelKey: 'assistant.intentEat', promptKey: 'assistant.promptEat' },
    { icon: 'map-outline', labelKey: 'assistant.intentPlanDay', promptKey: 'assistant.promptPlanDay' },
    { icon: 'telescope-outline', labelKey: 'assistant.intentExperience', promptKey: 'assistant.promptExperience' },
    { icon: 'heart-outline', labelKey: 'assistant.intentCouple', promptKey: 'assistant.promptCouple' },
    { icon: 'people-outline', labelKey: 'assistant.intentFamily', promptKey: 'assistant.promptFamily' },
  ];

  /** Importes sugeridos en el selector de presupuesto. */
  readonly budgetPresets = [200000, 500000, 1000000];

  /** El selector de presupuesto está abierto (intención "Tengo un presupuesto"). */
  readonly budgetOpen = signal(false);

  /** Monto elegido por el usuario; 0 = sin valor. */
  private readonly budgetAmount = signal(0);

  /** Vista formateada del monto (separador de miles), con prefijo en la plantilla. */
  readonly budgetDisplay = computed(() => {
    const value = this.budgetAmount();
    return value > 0
      ? new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(value)
      : '';
  });

  private readonly budgetInput = viewChild<ElementRef<HTMLInputElement>>('budgetInput');

  readonly examples: TranslationKey[] = [
    'assistant.example1',
    'assistant.example2',
    'assistant.example3',
    'assistant.example4',
  ];

  readonly refineKeys: TranslationKey[] = [
    'assistant.refineCheaper',
    'assistant.refineCalmer',
    'assistant.refineAdventure',
    'assistant.refineChangeFood',
  ];

  readonly fallbackTiles: FallbackTile[] = [
    { icon: 'compass-outline', labelKey: 'assistant.fallbackTours', route: '/tours' },
    { icon: 'restaurant-outline', labelKey: 'assistant.fallbackEat', route: '/restaurants' },
    { icon: 'bed-outline', labelKey: 'assistant.fallbackStay', route: '/inns' },
    { icon: 'boat-outline', labelKey: 'assistant.fallbackExperiences', route: '/tours' },
  ];

  private conversationId: string | null = null;
  private nextId = 1;
  private lastUserText = '';

  ngOnInit(): void {
    // Pregunta enviada desde los chips del centro de contacto (`/assistant?q=`).
    // Se consume una sola vez y se limpia de la URL para que volver atrás no
    // reenvíe el mensaje.
    const preset = this.route.snapshot.queryParamMap.get('q')?.trim();

    if (preset) {
      this.send(preset);
      this.location.replaceState('/assistant');
    }
  }

  newConversation(): void {
    if (this.messages().length === 0) {
      return;
    }
    void this.haptics.selection();
    this.messages.set([]);
    this.conversationId = null;
    this.lastUserText = '';
    this.focusComposer();
  }

  onInput(event: Event): void {
    const el = event.target as HTMLTextAreaElement;
    this.draft.set(el.value);
    this.autoGrow(el);
  }

  /** Enter envía; Shift+Enter hace salto de línea. */
  onEnter(event: Event): void {
    const keyboard = event as KeyboardEvent;
    if (keyboard.shiftKey) {
      return;
    }
    keyboard.preventDefault();
    this.send();
  }

  /** Envía el borrador actual o el texto recibido (chips/intenciones). */
  send(text?: string): void {
    const value = (text ?? this.draft()).trim();

    if (value === '' || this.sending()) {
      return;
    }

    void this.haptics.selection();
    this.draft.set('');
    this.resetComposerHeight();
    this.lastUserText = value;

    this.pushMessage({
      role: 'user',
      text: value,
      recommendations: [],
      packages: [],
      error: false,
    });

    this.sending.set(true);
    this.scrollToBottom();

    this.assistant.send(value, this.conversationId).subscribe({
      next: (reply) => {
        this.conversationId = reply.conversationId;
        this.pushMessage({
          role: 'assistant',
          text: reply.message,
          recommendations: reply.recommendations,
          packages: reply.packages,
          error: false,
        });
      },
      error: () => {
        this.pushMessage({
          role: 'assistant',
          text: this.i18n.t('assistant.error'),
          recommendations: [],
          packages: [],
          error: true,
        });
      },
      complete: () => {
        this.sending.set(false);
        this.scrollToBottom();
      },
    });
  }

  retry(): void {
    if (this.lastUserText === '' || this.sending()) {
      return;
    }
    this.send(this.lastUserText);
  }

  /** Envía la clave de una intención rápida ya resuelta al idioma activo. */
  sendFromKey(key: TranslationKey): void {
    // "Tengo un presupuesto" no envía un monto fijo: abre el selector para que
    // el viajero escriba el valor que quiera.
    if (key === 'assistant.promptBudget') {
      this.openBudgetSheet();
      return;
    }
    this.send(this.i18n.t(key));
  }

  /** Abre el selector de presupuesto con el campo vacío listo para escribir. */
  private openBudgetSheet(): void {
    void this.haptics.selection();
    this.budgetAmount.set(0);
    this.budgetOpen.set(true);
    setTimeout(() => this.budgetInput()?.nativeElement.focus());
  }

  closeBudgetSheet(): void {
    this.budgetOpen.set(false);
  }

  /** Rellena el monto con uno de los sugeridos. */
  choosePreset(amount: number): void {
    void this.haptics.selection();
    this.setBudgetAmount(amount);
  }

  /** ¿Este sugerido es el monto activo? (para el estado seleccionado del chip). */
  isPreset(amount: number): boolean {
    return this.budgetAmount() === amount;
  }

  /** Normaliza lo escrito a dígitos y re-formatea el campo con separador de miles. */
  onBudgetInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const digits = input.value.replace(/[^\d]/g, '');
    this.budgetAmount.set(digits ? Number.parseInt(digits, 10) : 0);
    input.value = this.budgetDisplay();
  }

  submitBudget(): void {
    const value = this.budgetAmount();
    if (value <= 0 || this.sending()) {
      return;
    }
    const amount = formatCurrency(value, 'COP');
    this.budgetOpen.set(false);
    this.send(this.i18n.t('assistant.promptBudgetValue', { amount }));
  }

  private setBudgetAmount(amount: number): void {
    this.budgetAmount.set(amount);
    const input = this.budgetInput()?.nativeElement;
    if (input) {
      input.value = this.budgetDisplay();
    }
  }

  /** Envía un ejemplo ya traducido al idioma activo. */
  sendExample(key: TranslationKey): void {
    this.send(this.i18n.t(key));
  }

  /** Refina el plan enviando una instrucción nueva al agente real. */
  sendRefine(key: TranslationKey): void {
    this.send(this.i18n.t(key));
  }

  /** ¿El turno debe mostrarse como comparación? 2–3 opciones del mismo tipo. */
  isComparison(message: AssistantChatMessage): boolean {
    if (message.packages.length > 0) {
      return false;
    }
    const recs = message.recommendations;
    if (recs.length < 2 || recs.length > 3) {
      return false;
    }
    const first: AssistantItemType = recs[0].type;
    return recs.every((rec) => rec.type === first);
  }

  openRecommendation(recommendation: AssistantRecommendation): void {
    if (!recommendation.route) {
      return;
    }
    void this.haptics.selection();
    void this.router.navigate(recommendation.route);
  }

  go(route: string): void {
    void this.haptics.selection();
    void this.router.navigate([route]);
  }

  explore(): void {
    this.go('/explore');
  }

  private pushMessage(message: Omit<AssistantChatMessage, 'id'>): void {
    this.messages.update((current) => [...current, { ...message, id: this.nextId++ }]);
  }

  private focusComposer(): void {
    setTimeout(() => this.composer()?.nativeElement.focus());
  }

  /** Baja el hilo hasta el último turno una vez Angular pintó el cambio. */
  private scrollToBottom(): void {
    setTimeout(() => void this.scroll()?.scrollToBottom(300), 60);
  }

  /** Crecimiento del composer hasta un máximo razonable. */
  private autoGrow(el: HTMLTextAreaElement): void {
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, 120)}px`;
  }

  private resetComposerHeight(): void {
    const el = this.composer()?.nativeElement;
    if (el) {
      el.style.height = 'auto';
    }
  }
}
