import type { Language } from '../../core/services/translation.service';

/**
 * Contenido legal de la Política de Privacidad de SEVEN.
 *
 * A diferencia del resto de textos de la app, esto no son cadenas cortas de
 * interfaz: es un documento. Por eso vive en su propio archivo y no en
 * `TranslationService`, que se llena de las etiquetas de botones y menús.
 *
 * El español es el texto oficial (los datos se recogen en San Andrés,
 * Colombia) y los otros dos idiomas son traducciones obligatorias: los tres
 * diccionarios se tipan contra `PrivacyContent`, así que agregar una sección
 * o cambiar un título obliga a traducirla o rompe la compilación.
 */
export interface PrivacyHighlight {
  readonly icon: string;
  readonly title: string;
  readonly text: string;
}

export interface PrivacyTable {
  readonly columns: readonly string[];
  readonly rows: readonly (readonly string[])[];
}

export interface PrivacySection {
  /** Ancla usada por el índice y por el scroll de la pantalla. */
  readonly id: string;
  /** Número de sección para el rótulo ('01', '02'…). */
  readonly number: string;
  readonly icon: string;
  readonly title: string;
  readonly paragraphs: readonly string[];
  readonly bullets?: readonly string[];
  readonly table?: PrivacyTable;
  /** Enlace a fuente externa (políticas de terceros, autoridades). */
  readonly link?: { readonly label: string; readonly href: string };
}

export interface PrivacyContent {
  readonly badge: string;
  readonly title: string;
  readonly subtitle: string;
  readonly updatedLabel: string;
  readonly updatedOn: string;
  readonly intro: string;
  readonly onThisPage: string;
  readonly highlightsTitle: string;
  readonly highlights: readonly PrivacyHighlight[];
  readonly inventoryTitle: string;
  readonly inventoryHint: string;
  readonly inventoryEmpty: string;
  readonly inventoryInstallId: string;
  readonly inventoryFavorites: string;
  readonly inventoryRecent: string;
  readonly inventoryBookings: string;
  readonly inventoryGuest: string;
  readonly inventoryLanguage: string;
  readonly controlsTitle: string;
  readonly controlsHint: string;
  readonly actionDevice: string;
  readonly actionDeviceHint: string;
  readonly actionEverything: string;
  readonly actionEverythingHint: string;
  readonly actionEmail: string;
  readonly actionEmailHint: string;
  readonly confirmDeviceTitle: string;
  readonly confirmDeviceBody: string;
  readonly confirmDeviceConfirm: string;
  readonly confirmEverythingTitle: string;
  readonly confirmEverythingBody: string;
  readonly confirmEverythingConfirm: string;
  readonly cancel: string;
  readonly working: string;
  readonly doneDevice: string;
  readonly doneEverything: string;
  readonly donePartial: string;
  readonly close: string;
  readonly footer: string;
  readonly sections: readonly PrivacySection[];
}

const es: PrivacyContent = {
  badge: 'Legal',
  title: 'Política de Privacidad',
  subtitle:
    'Qué datos toca SEVEN, dónde viven, quién los ve y cómo borrarlos. Sin letra pequeña: esta pantalla también te deja eliminarlos con un botón.',
  updatedLabel: 'Última actualización',
  updatedOn: '27 de septiembre de 2026',
  intro:
    'SEVEN es una app de turismo para San Andrés y las islas. Funciona sin cuentas, sin contraseñas y sin publicidad. Esta política explica, en español claro, cada dato que la app maneja: los que se quedan en tu teléfono, los que llegan a nuestros servidores y los que se comparten con terceros. Si algo no está aquí, no lo recogemos.',
  onThisPage: 'En esta página',
  highlightsTitle: 'Privacidad de un vistazo',
  highlights: [
    {
      icon: 'person-circle-outline',
      title: 'Sin cuenta ni login',
      text: 'No pedimos correo, contraseña ni documento. No hay perfil de usuario que rastrear.',
    },
    {
      icon: 'ban-outline',
      title: 'Sin publicidad ni perfiles',
      text: 'No hay SDK de publicidad, ni píxeles, ni venta de datos. No hay nada con qué perfilarte.',
    },
    {
      icon: 'eye-outline',
      title: 'Sin analítica oculta',
      text: 'No rastreamos sesiones ni eventos con fines de marketing. Solo los datos que una reserva necesita.',
    },
    {
      icon: 'trash-outline',
      title: 'Borrado en un toque',
      text: 'Puedes eliminar todo lo que la app sabe de ti desde esta misma pantalla.',
    },
  ],
  inventoryTitle: 'Tus datos en este dispositivo',
  inventoryHint:
    'Esto es una lectura real de lo guardado ahora mismo en este teléfono, no una descripción genérica. Los datos entre corchetes son los tuyos.',
  inventoryEmpty: 'No hay datos guardados en este dispositivo.',
  inventoryInstallId: 'Identificador del dispositivo',
  inventoryFavorites: 'Servicios guardados',
  inventoryRecent: 'Vistos recientemente',
  inventoryBookings: 'Reservas guardadas',
  inventoryGuest: 'Datos de contacto para prellenar',
  inventoryLanguage: 'Idioma de la app',
  controlsTitle: 'Tus controles',
  controlsHint:
    'Elige hasta dónde quieres llegar. Puedes hacerlo y seguir usando la app con normalidad: si borras el identificador del dispositivo, tus reservas antiguas dejarán de verse en este teléfono.',
  actionDevice: 'Borrar los datos de este dispositivo',
  actionDeviceHint:
    'Limpia idioma, preferencias, guardados, historial, datos de contacto, reservas locales y notificaciones programadas. No toca el servidor.',
  actionEverything: 'Borrar todo, incluidas mis reservas',
  actionEverythingHint:
    'Primero cancela cada una de tus reservas en el servidor y después limpia el dispositivo. Los proveedores dejan de recibir información de esas solicitudes.',
  actionEmail: 'Enviar una solicitud de borrado',
  actionEmailHint:
    'Abre tu correo con la solicitud ya redactada y tu identificador de dispositivo incluido. Responde una persona del equipo, normalmente en 15 días hábiles.',
  confirmDeviceTitle: '¿Borrar los datos de este dispositivo?',
  confirmDeviceBody:
    'Se borrarán el idioma, la preferencia de animaciones, el identificador del dispositivo, los servicios guardados, el historial, tus datos de contacto prellenados, las reservas locales y las notificaciones ya programadas. Esta acción no se puede deshacer.',
  confirmDeviceConfirm: 'Sí, borrar',
  confirmEverythingTitle: '¿Borrar todo y cancelar mis reservas?',
  confirmEverythingBody:
    'Se cancelarán en el servidor todas las reservas guardadas en este dispositivo y luego se limpiará el teléfono. Si una reserva ya estaba confirmada por un proveedor, cancélala también con él para evitar cargos. Las reservas ya facturadas o consumidas se conservan por obligación legal y solo se eliminan tras el plazo de retención.',
  confirmEverythingConfirm: 'Sí, borrar y cancelar',
  cancel: 'Cancelar',
  working: 'Borrando…',
  doneDevice: 'Listo. Este dispositivo ya no guarda datos tuyos.',
  doneEverything: 'Listo. Se borró el dispositivo y se cancelaron {count} reserva(s).',
  donePartial: 'Se borró el dispositivo, pero {failed} reserva(s) no se pudieron cancelar. Escríbenos para terminarlas.',
  close: 'Entendido',
  footer:
    'SEVEN · San Andrés, Islas Colombianas, Colombia. Esta política aplica a la app móvil y a este sitio; si alguna versión difiere, prevalece la versión en español.',
  sections: [
    {
      id: 'identidad',
      number: '01',
      icon: 'business-outline',
      title: 'Quién es responsable de tus datos',
      paragraphs: [
        'El responsable del tratamiento de tus datos personales es SEVEN, la plataforma turística que publica esta app, con sede en San Andrés, Islas Colombianas, Colombia.',
        'No hay una sociedad gigante escondida detrás de un formulario: la app es el producto. Para cualquier asunto relacionado con privacidad puedes escribirnos a privacidad@seven.app y responden las mismas personas que operan el servicio.',
      ],
      bullets: [
        'Responsable: SEVEN (operador de la app y del catálogo).',
        'Contacto de privacidad: privacidad@seven.app.',
        'Autoridad de control en Colombia: Superintendencia de Industria y Comercio (SIC).',
      ],
    },
    {
      id: 'resumen',
      number: '02',
      icon: 'shield-checkmark-outline',
      title: 'Resumen: qué hacemos y qué no',
      paragraphs: [
        'SEVEN está diseñada para recoger lo mínimo. Se puede navegar el catálogo completo, ver restaurantes, posadas y tours, y guardar favoritos sin dejar ningún dato en nuestros servidores.',
        'Lo único que sale de tu teléfono hacia nosotros es una reserva que tú decides hacer, y el identificador técnico del dispositivo necesario para que esa reserva llegue a ti.',
      ],
      bullets: [
        'No hay registro ni login: no creamos perfiles de usuario.',
        'No usamos cookies publicitarias, píxeles de seguimiento ni huella digital.',
        'No vendemos, alquilamos ni cedemos tus datos con fines comerciales.',
        'No usamos tu actividad para publicidad comportamental.',
        'No tomamos decisiones automatizadas que produzcan efectos jurídicos sobre ti.',
      ],
    },
    {
      id: 'datos',
      number: '03',
      icon: 'clipboard-outline',
      title: 'Qué datos recogemos',
      paragraphs: [
        'La tabla resume cada dato, dónde se guarda, para qué se usa y por qué es lícito tratarlo. Las bases legales son las del RGPD (art. 6) y de la Ley 1581 de 2012 de Colombia.',
      ],
      table: {
        columns: ['Dato', 'Dónde vive', 'Para qué', 'Base legal'],
        rows: [
          [
            'Nombre y teléfono',
            'Servidor, y una copia en tu dispositivo para prellenar el formulario',
            'Atender la reserva y que el proveedor te contacte para confirmarla',
            'Ejecución de contrato / consentimiento',
          ],
          [
            'Correo electrónico (solo si lo proporcionas)',
            'Servidor',
            'Confirmaciones por correo de los cambios de estado',
            'Consentimiento',
          ],
          [
            'Fecha, número de personas, tipo de experiencia, monto y solicitudes especiales',
            'Servidor',
            'Reservar, cobrar y entregar el servicio que pediste',
            'Ejecución de contrato',
          ],
          [
            'Código de reserva e historial de estados',
            'Servidor',
            'Que puedas seguir tu reserva y el proveedor la gestione',
            'Ejecución de contrato / interés legítimo',
          ],
          [
            'Identificador del dispositivo (install_id, UUID)',
            'Servidor',
            'Vincula una reserva con el teléfono que la creó, sin necesidad de cuenta ni correo',
            'Ejecución de contrato',
          ],
          [
            'Dirección IP, navegador y hora de la petición',
            'Registros del servidor (90 días)',
            'Seguridad, prevenir abuso y diagnosticar fallos',
            'Interés legítimo',
          ],
        ],
      },
    },
    {
      id: 'dispositivo',
      number: '04',
      icon: 'phone-portrait-outline',
      title: 'Qué se guarda solo en tu teléfono',
      paragraphs: [
        'Estos datos nunca salen de tu dispositivo mientras no hagas una reserva. Puedes ver cuáles existen ahora mismo en el inventario de esta pantalla y borrarlos con un botón.',
      ],
      table: {
        columns: ['Clave', 'Qué es', 'Por qué existe'],
        rows: [
          ['seven_language', 'Idioma de la interfaz (es, en, pt)', 'Para que la app se abra en el idioma que elegiste'],
          ['seven_reduce_motion', 'Preferencia de animaciones', 'Accesibilidad'],
          ['seven_welcome_completed', 'Marca de que ya viste la pantalla de bienvenida', 'No mostrarte la introducción otra vez'],
          ['seven_install_id', 'Identificador único de esta instalación', 'Saber qué reservas son de este teléfono'],
          ['seven:guest', 'Nombre y teléfono que escribiste la última vez', 'No repetirlos en cada reserva'],
          ['seven:favorites', 'Servicios que guardaste', 'Tu lista de deseos, solo tuya'],
          ['seven:recent', 'Últimos servicios vistos (máx. 20)', 'Reabrir rápido lo que estabas viendo'],
          ['seven:bookings', 'Copia local de tus reservas y sus códigos', 'Verlas sin red y avisarte de los cambios'],
        ],
      },
    },
    {
      id: 'permisos',
      number: '05',
      icon: 'navigate-outline',
      title: 'Permisos del teléfono',
      paragraphs: [
        'La app pide permisos del sistema solo cuando una función los necesita, y cada permiso se puede revocar desde los ajustes de tu teléfono sin que la app deje de funcionar.',
      ],
      bullets: [
        'Ubicación: se usa solo cuando pulsas "¿cómo llego?" en un tour, para estimar la distancia y el tiempo hasta el punto de encuentro. La coordenada se procesa en el teléfono y no se envía a nuestros servidores.',
        'Notificaciones: solo en la app instalada en el teléfono, para avisarte de que una reserva fue confirmada, cancelada o completada. No hay push de terceros ni campañas.',
        'Compartir: abre el panel de compartir del sistema cuando decides compartir un tour. Lo decides tú, y el contenido lo elige la app o tú.',
        'Vibración (háptica): retroalimentación táctil al tocar. No recoge ni transmite nada.',
        'Red: se usa para saber si hay conexión y mostrar el aviso de modo offline. No identifica tu red.',
      ],
    },
    {
      id: 'finalidad',
      number: '06',
      icon: 'flag-outline',
      title: 'Para qué usamos los datos',
      paragraphs: [
        'No te perfílamos, no mejoramos un modelo publicitario con tu actividad y no te contactamos con ofertas salvo que reserves y dejes un medio de contacto.',
      ],
      bullets: [
        'Mostrarte el catálogo de la isla: datos públicos de negocios, nunca datos personales.',
        'Crear y gestionar tu reserva, y avisarte de sus cambios.',
        'Guardar tus preferencias y tus guardados en el dispositivo.',
        'Mantener la app segura y funcionando: registros y límites antiabuso.',
        'Cumplir obligaciones legales cuando apliquen.',
      ],
    },
    {
      id: 'terceros',
      number: '07',
      icon: 'git-network-outline',
      title: 'Terceros que ven parte de tus datos',
      paragraphs: [
        'SEVEN no lo tiene todo en su propia infraestructura. Estos proveedores reciben datos solo en los escenarios descritos; ninguno recibe tu información de reservas por nuestra cuenta.',
      ],
      table: {
        columns: ['Tercero', 'Qué recibe', 'Cuándo'],
        rows: [
          [
            'Google Fonts',
            'Tu dirección IP y tu agente de usuario al cargar las tipografías Oswald y Poppins',
            'Cada vez que abres la app',
          ],
          [
            'WhatsApp (Meta)',
            'Lo que tú escribes y el mensaje con el código de reserva',
            'Cuando tocas un botón de WhatsApp para hablar con un proveedor',
          ],
          [
            'Instagram y Facebook (Meta)',
            'Tu actividad en sus plataformas',
            'Solo si abres nuestros perfiles desde la app',
          ],
          [
            'Proveedor de correo electrónico',
            'Nombre, correo (si lo diste) y estado de la reserva',
            'Cuando se envía una confirmación de estado',
          ],
          [
            'Proveedor de hosting y base de datos',
            'Los datos de la reserva y el identificador del dispositivo',
            'Cuando reservas, mientras dure el registro',
          ],
          [
            'Tiendas de apps (Google Play, App Store)',
            'Metadatos de la instalación',
            'Cuando instalas o actualizas la app',
          ],
        ],
      },
      link: {
        label: 'Ver la política de privacidad de Google Fonts',
        href: 'https://policies.google.com/privacy',
      },
    },
    {
      id: 'ia',
      number: '08',
      icon: 'hardware-chip-outline',
      title: 'Transparencia: esta app fue hecha con IA',
      paragraphs: [
        'Lo decimos claro porque nos parece lo correcto: el diseño, la interfaz, los textos y el código de SEVEN se crearon con asistencia de inteligencia artificial, bajo dirección y revisión humanas.',
        'Lo que la IA no hace en este producto: no hay chatbot, ni motor de recomendaciones, ni análisis de voz, rostro o biometría, ni generación de contenido personalizada. Ningún modelo de IA procesa los datos personales de quien usa la app.',
        'Durante el desarrollo no se alimentó a esas herramientas con datos reales de viajeros: el catálogo de ejemplo y los testimonios visibles en la app son ficticios y sirven solo de muestra. El contenido que describe negocios reales (fotos, precios, disponibilidad) lo carga y lo valida cada proveedor.',
        'Todo el contenido publicado pasa por revisión humana antes de estar visible. Si alguna vez se incorpora una función de IA que procese datos personales, esta política se actualizará antes de su lanzamiento y se pedirá tu consentimiento si la ley lo exige.',
      ],
    },
    {
      id: 'cookies',
      number: '09',
      icon: 'server-outline',
      title: 'Cookies y almacenamiento local',
      paragraphs: [
        'SEVEN no usa cookies. No hay cookies de sesión, ni de publicidad, ni de analítica. La app funciona con almacenamiento local del navegador o del sistema (localStorage e IndexedDB), que solo lee esta aplicación y no puede seguirte entre sitios.',
        'Puedes vaciarlo cuando quieras desde esta pantalla, desde los ajustes de tu teléfono, o borrando los datos de la app.',
      ],
    },
    {
      id: 'conservacion',
      number: '10',
      icon: 'time-outline',
      title: 'Cuánto tiempo conservamos los datos',
      paragraphs: [
        'No guardamos datos indefinidamente. Estos son los plazos, y ninguno depende de que tú te acuerdes de pedirnos nada:',
      ],
      bullets: [
        'En el dispositivo: hasta que los borres, borres los datos de la app o la desinstales.',
        'Reservas: mientras la reserva esté vigente y hasta 12 meses después de completarse o cancelarse, para atender consultas y reclamaciones. Después se eliminan o se anonimizan de forma irreversible.',
        'Registros del servidor (IP, navegador): 90 días.',
        'Correos de confirmación: hasta 12 meses después de la reserva.',
        'Copias en proveedores: mientras la reserva esté activa y hasta 30 días adicionales tras su cierre.',
      ],
    },
    {
      id: 'derechos',
      number: '11',
      icon: 'checkmark-done-outline',
      title: 'Tus derechos',
      paragraphs: [
        'La Ley 1581 de 2012 (Colombia) y el RGPD te dan, entre otros, los siguientes derechos. Para ejercerlos basta con escribirnos: respondemos en un máximo de 15 días hábiles y sin pedirte nada que no necesitemos para identificarte.',
      ],
      bullets: [
        'Acceso: saber qué datos tuyos tenemos y entregarte una copia.',
        'Rectificación: corregir datos inexactos o desactualizados.',
        'Supresión: borrar tus datos cuando ya no haya una razón legítima para conservarlos.',
        'Portabilidad: recibir tus datos en un formato estructurado y de uso común.',
        'Revocación del consentimiento: retirarlo en cualquier momento, sin que ello afecte la licitud del tratamiento previo.',
        'Oposición y limitación: oponerte a un tratamiento o pedir que lo restrinjamos.',
        'No discriminación: ejercer tus derechos nunca afecta negativamente al trato que recibes.',
        'Reclamación: ante la SIC de Colombia; si estás en la UE o el EEE, ante la autoridad de control de tu país; en California, ante el Attorney General bajo la CCPA/CPRA.',
      ],
    },
    {
      id: 'borrado',
      number: '12',
      icon: 'trash-outline',
      title: 'Cómo borrar tus datos',
      paragraphs: [
        'Tienes tres caminos y ninguno requiere una llamada. El más rápido es el botón de esta pantalla, y esos botones borran de verdad: no esconden la información.',
      ],
      bullets: [
        'Desde la app: borra el dispositivo al instante y cancela tus reservas guardadas en el servidor con un paso. Ideal si el teléfono no es tuyo, o si simplemente quieres empezar de cero.',
        'Por correo: escribe a privacidad@seven.app con tu identificador de dispositivo y el código de reserva. Verificamos que seas tú y eliminamos el registro; si hay algo que estamos obligados a conservar, te lo decimos y por qué.',
        'Solicitud verbal: también la aceptamos. Basta con que nos escribas y borramos la información asociada al código o al identificador de dispositivo que nos indiques.',
      ],
    },
    {
      id: 'seguridad',
      number: '13',
      icon: 'lock-closed-outline',
      title: 'Seguridad',
      paragraphs: [
        'Aplicamos medidas técnicas y organizativas razonables para proteger la información que nos das. Ningún sistema es infalible, pero estas son las prácticas con las que cuidamos tus datos desde el primer día:',
      ],
      bullets: [
        'Todo el tráfico entre la app y el servidor viaja cifrado con HTTPS/TLS.',
        'No guardamos contraseñas porque no hay cuentas ni credenciales que gestionar.',
        'El identificador del dispositivo no es una credencial: por sí solo no da acceso a nada. Para ver o cancelar una reserva hacen falta el código y ese identificador.',
        'El acceso interno a los registros de reservas está restringido al personal que lo necesita para operar el servicio.',
        'No almacenamos datos de tarjetas: la app no procesa pagos, el pago se hace directamente con cada proveedor.',
      ],
    },
    {
      id: 'menores',
      number: '14',
      icon: 'people-outline',
      title: 'Menores de edad',
      paragraphs: [
        'SEVEN está dirigida a personas mayores de 18 años. No es un servicio dirigido a menores ni recopilamos de forma consciente datos de menores de edad. Si eres menor, haz la reserva con un adulto.',
        'Si crees que un menor nos facilitó datos, escríbenos y los borramos de inmediato.',
      ],
    },
    {
      id: 'cambios',
      number: '15',
      icon: 'document-text-outline',
      title: 'Cambios en esta política',
      paragraphs: [
        'Actualizamos esta política cuando cambia el producto o la ley. La fecha del principio indica la última versión. Si el cambio afecta de forma material a tus derechos, lo avisaremos en la app antes de que la nueva versión entre en vigor.',
        'Puedes escribirnos para pedir una copia de la versión que estaba publicada cuando hiciste tu reserva.',
      ],
    },
    {
      id: 'contacto',
      number: '16',
      icon: 'mail-outline',
      title: 'Contacto',
      paragraphs: [
        'Escríbenos a privacidad@seven.app. Atendemos en español e inglés, de lunes a viernes en horario de San Andrés. También puedes escribirnos por los canales de contacto de la sección Acerca de.',
        'Nos tomamos en serio la privacidad: si algo de esta política no coincide con lo que viviste, preferimos que lo sepas y lo arreglemos.',
      ],
      bullets: [
        'Correo: privacidad@seven.app',
        'Supervisión: Superintendencia de Industria y Comercio (SIC), Colombia.',
      ],
    },
  ],
};

const en: PrivacyContent = {
  badge: 'Legal',
  title: 'Privacy Policy',
  subtitle:
    'What data SEVEN touches, where it lives, who can see it and how to delete it. No fine print: this screen also lets you erase it with one tap.',
  updatedLabel: 'Last updated',
  updatedOn: 'September 27, 2026',
  intro:
    'SEVEN is a travel app for San Andrés and the islands. It runs without accounts, passwords or advertising. This policy explains, in plain English, every piece of data the app handles: what stays on your phone, what reaches our servers and what is shared with third parties. If it is not listed here, we do not collect it.',
  onThisPage: 'On this page',
  highlightsTitle: 'Privacy at a glance',
  highlights: [
    {
      icon: 'person-circle-outline',
      title: 'No account, no login',
      text: 'We never ask for an email, a password or an ID. There is no user profile to track.',
    },
    {
      icon: 'ban-outline',
      title: 'No ads, no profiling',
      text: 'No ad SDKs, no pixels, no selling data. There is nothing to profile you with.',
    },
    {
      icon: 'eye-outline',
      title: 'No hidden analytics',
      text: 'We do not track sessions or events for marketing. Only the data a booking needs.',
    },
    {
      icon: 'trash-outline',
      title: 'One-tap deletion',
      text: 'You can erase everything the app knows about you from this very screen.',
    },
  ],
  inventoryTitle: 'Your data on this device',
  inventoryHint:
    'This is a real read of what is stored on this phone right now, not a generic description. The values below are your own data.',
  inventoryEmpty: 'No data stored on this device.',
  inventoryInstallId: 'Device identifier',
  inventoryFavorites: 'Saved places',
  inventoryRecent: 'Recently viewed',
  inventoryBookings: 'Saved bookings',
  inventoryGuest: 'Contact details used to prefill',
  inventoryLanguage: 'App language',
  controlsTitle: 'Your controls',
  controlsHint:
    'Choose how far you want to go. You can do this and keep using the app normally: if you delete the device identifier, your older bookings will stop showing on this phone.',
  actionDevice: 'Erase data on this device',
  actionDeviceHint:
    'Clears language, preferences, saved places, history, contact details, local bookings and scheduled notifications. Does not touch the server.',
  actionEverything: 'Erase everything, including bookings',
  actionEverythingHint:
    'First cancels each of your bookings on the server, then wipes the device. Providers stop receiving information about those requests.',
  actionEmail: 'Send an erasure request',
  actionEmailHint:
    'Opens your email app with the request already written and your device identifier included. A person from the team replies, usually within 15 business days.',
  confirmDeviceTitle: 'Erase data on this device?',
  confirmDeviceBody:
    'This clears your language, motion preference, device identifier, saved places, history, prefilled contact details, local bookings and any scheduled notification. This action cannot be undone.',
  confirmDeviceConfirm: 'Yes, erase',
  confirmEverythingTitle: 'Erase everything and cancel bookings?',
  confirmEverythingBody:
    'Every booking stored on this device will be cancelled on the server, then the phone will be wiped. If a booking was already confirmed by a provider, cancel it with them too to avoid charges. Bookings already paid or consumed are kept for a legal retention period and are purged once it expires.',
  confirmEverythingConfirm: 'Yes, erase and cancel',
  cancel: 'Cancel',
  working: 'Erasing…',
  doneDevice: 'Done. This device no longer holds any of your data.',
  doneEverything: 'Done. Device erased and {count} booking(s) cancelled.',
  donePartial: 'Device erased, but {failed} booking(s) could not be cancelled. Write to us to finish them.',
  close: 'Got it',
  footer:
    'SEVEN · San Andrés, Colombian Islands, Colombia. This policy covers the mobile app and this website; if any version differs, the Spanish one prevails.',
  sections: [
    {
      id: 'identidad',
      number: '01',
      icon: 'business-outline',
      title: 'Who is responsible for your data',
      paragraphs: [
        'The data controller is SEVEN, the tourism platform that publishes this app, based in San Andrés, Colombian Islands, Colombia.',
        'There is no faceless company behind a sign-up form: the app is the product. For anything privacy-related, write to us at privacidad@seven.app and the same people who run the service will answer.',
      ],
      bullets: [
        'Controller: SEVEN (operator of the app and of the catalogue).',
        'Privacy contact: privacidad@seven.app.',
        'Colombian supervisory authority: Superintendencia de Industria y Comercio (SIC).',
      ],
    },
    {
      id: 'resumen',
      number: '02',
      icon: 'shield-checkmark-outline',
      title: 'In short: what we do and what we do not',
      paragraphs: [
        'SEVEN is built to collect as little as possible. You can browse the whole catalogue, look at restaurants, inns and tours, and bookmark places without leaving a single record on our servers.',
        'The only thing that leaves your phone towards us is a booking you decide to make, plus the technical device identifier needed for that booking to reach you.',
      ],
      bullets: [
        'No sign-up and no login: we do not create user profiles.',
        'No advertising cookies, tracking pixels or fingerprinting.',
        'We do not sell, rent or trade your data for commercial purposes.',
        'Your activity is never used for behavioural advertising.',
        'No automated decision-making produces legal effects for you.',
      ],
    },
    {
      id: 'datos',
      number: '03',
      icon: 'clipboard-outline',
      title: 'What data we collect',
      paragraphs: [
        'The table lists every item, where it is stored, why we need it and the legal ground for processing it. Legal grounds follow the GDPR (Art. 6) and Colombia’s Law 1581 of 2012.',
      ],
      table: {
        columns: ['Data', 'Where it lives', 'Why', 'Legal ground'],
        rows: [
          [
            'Name and phone number',
            'Server, plus a copy on your device to prefill the form',
            'Handle the booking and let the provider reach you to confirm it',
            'Performance of a contract / consent',
          ],
          [
            'Email address (only if you provide it)',
            'Server',
            'Email confirmations when the booking status changes',
            'Consent',
          ],
          [
            'Date, party size, experience type, amount and special requests',
            'Server',
            'Reserve, charge and deliver the service you asked for',
            'Performance of a contract',
          ],
          [
            'Booking code and status history',
            'Server',
            'Let you follow your booking and let the provider manage it',
            'Performance of a contract / legitimate interest',
          ],
          [
            'Device identifier (install_id, UUID)',
            'Server',
            'Links a booking to the phone that created it, with no account or email needed',
            'Performance of a contract',
          ],
          [
            'IP address, browser and request time',
            'Server logs (90 days)',
            'Security, abuse prevention and troubleshooting',
            'Legitimate interest',
          ],
        ],
      },
    },
    {
      id: 'dispositivo',
      number: '04',
      icon: 'phone-portrait-outline',
      title: 'What stays only on your phone',
      paragraphs: [
        'None of this data leaves your device unless you make a booking. You can see what exists right now in the inventory on this screen and erase it with one button.',
      ],
      table: {
        columns: ['Key', 'What it is', 'Why it exists'],
        rows: [
          ['seven_language', 'Interface language (es, en, pt)', 'So the app opens in the language you picked'],
          ['seven_reduce_motion', 'Reduced-motion preference', 'Accessibility'],
          ['seven_welcome_completed', 'Flag saying you already saw the welcome screen', 'Not showing the intro again'],
          ['seven_install_id', 'Unique identifier for this install', 'Know which bookings belong to this phone'],
          ['seven:guest', 'Name and phone you typed last time', 'Not repeating them on every booking'],
          ['seven:favorites', 'Places you saved', 'Your wish list, yours only'],
          ['seven:recent', 'Last places viewed (max. 20)', 'Reopen quickly what you were looking at'],
          ['seven:bookings', 'Local copy of your bookings and codes', 'See them offline and get status alerts'],
        ],
      },
    },
    {
      id: 'permisos',
      number: '05',
      icon: 'navigate-outline',
      title: 'Phone permissions',
      paragraphs: [
        'The app asks for system permissions only when a feature needs them, and you can revoke any of them from your phone settings without breaking the app.',
      ],
      bullets: [
        'Location: used only when you tap “How do I get there?” on a tour, to estimate distance and travel time to the meeting point. The coordinates are processed on the phone and never sent to our servers.',
        'Notifications: in the installed app only, to tell you a booking was confirmed, cancelled or completed. No third-party push, no campaigns.',
        'Share: opens the system share sheet when you decide to share a tour. You decide, and the app suggests the content.',
        'Haptics: tactile feedback on tap. Collects and transmits nothing.',
        'Network: used to know whether there is a connection and show the offline notice. It does not identify your network.',
      ],
    },
    {
      id: 'finalidad',
      number: '06',
      icon: 'flag-outline',
      title: 'What we use your data for',
      paragraphs: [
        'We do not profile you, we do not train an advertising model on your activity, and we never contact you with offers unless you booked and left us a way to reach you.',
      ],
      bullets: [
        'Showing you the island catalogue: public business data, never personal data.',
        'Creating and managing your booking, and alerting you when it changes.',
        'Keeping your preferences and saved places on the device.',
        'Keeping the app secure and working: logs and anti-abuse limits.',
        'Meeting legal obligations where they apply.',
      ],
    },
    {
      id: 'terceros',
      number: '07',
      icon: 'git-network-outline',
      title: 'Third parties that see some of your data',
      paragraphs: [
        'SEVEN does not run everything on its own infrastructure. These providers receive data only in the scenarios described; none of them receives your booking information on our behalf.',
      ],
      table: {
        columns: ['Third party', 'What it receives', 'When'],
        rows: [
          [
            'Google Fonts',
            'Your IP address and user agent when loading the Oswald and Poppins fonts',
            'Every time you open the app',
          ],
          [
            'WhatsApp (Meta)',
            'Whatever you type, plus the message with your booking code',
            'When you tap a WhatsApp button to talk to a provider',
          ],
          [
            'Instagram and Facebook (Meta)',
            'Your activity on their platforms',
            'Only if you open our profiles from the app',
          ],
          [
            'Email service provider',
            'Name, email (if given) and booking status',
            'When a status confirmation is sent',
          ],
          [
            'Hosting and database provider',
            'Booking data and the device identifier',
            'When you book, while the record lives',
          ],
          [
            'App stores (Google Play, App Store)',
            'Install metadata',
            'When you install or update the app',
          ],
        ],
      },
      link: {
        label: 'Read the Google Fonts privacy policy',
        href: 'https://policies.google.com/privacy',
      },
    },
    {
      id: 'ia',
      number: '08',
      icon: 'hardware-chip-outline',
      title: 'Transparency: this app was built with AI',
      paragraphs: [
        'We say it plainly because we think it is the right thing to do: SEVEN’s design, interface, copy and code were created with artificial intelligence assistance, under human direction and review.',
        'What AI does not do in this product: there is no chatbot, no recommendation engine, no voice, face or biometric analysis, and no personalised content generation. No AI model processes the personal data of people using the app.',
        'During development those tools were never fed real traveller data: the sample catalogue and the testimonials shown in the app are fictional and exist only as a demo. Content describing real businesses (photos, prices, availability) is uploaded and validated by each provider.',
        'Everything published is reviewed by a human before it goes live. If we ever ship an AI feature that processes personal data, this policy will be updated before launch and we will ask for your consent if the law requires it.',
      ],
    },
    {
      id: 'cookies',
      number: '09',
      icon: 'server-outline',
      title: 'Cookies and local storage',
      paragraphs: [
        'SEVEN does not use cookies. No session cookies, no advertising cookies, no analytics cookies. The app runs on local browser or system storage (localStorage and IndexedDB), which only this app can read and which cannot follow you across sites.',
        'You can wipe it whenever you want from this screen, from your phone settings, or by clearing the app data.',
      ],
    },
    {
      id: 'conservacion',
      number: '10',
      icon: 'time-outline',
      title: 'How long we keep data',
      paragraphs: [
        'We never keep data indefinitely. These are the deadlines, and none of them depends on you remembering to ask us:',
      ],
      bullets: [
        'On your device: until you erase it, clear the app data or uninstall.',
        'Bookings: while the booking is active and up to 12 months after it is completed or cancelled, to handle questions and claims. After that they are deleted or irreversibly anonymised.',
        'Server logs (IP, browser): 90 days.',
        'Confirmation emails: up to 12 months after the booking.',
        'Provider copies: while the booking is active, plus 30 days after the booking ends.',
      ],
    },
    {
      id: 'derechos',
      number: '11',
      icon: 'checkmark-done-outline',
      title: 'Your rights',
      paragraphs: [
        'Colombia’s Law 1581 of 2012 and the GDPR give you, among others, the rights below. To exercise any of them just write to us: we answer within 15 business days and we will not ask for anything we do not need to verify who you are.',
      ],
      bullets: [
        'Access: know what data we hold about you and get a copy.',
        'Rectification: fix inaccurate or outdated data.',
        'Erasure: delete your data when there is no longer a legitimate reason to keep it.',
        'Portability: receive your data in a structured, commonly used format.',
        'Withdraw consent: pull your consent at any time, without affecting the lawfulness of earlier processing.',
        'Object and restrict: object to a processing or ask us to limit it.',
        'No discrimination: exercising your rights never affects how you are treated.',
        'Complaint: with the Colombian SIC; if you are in the EU/EEA, with your local authority; in California, with the Attorney General under the CCPA/CPRA.',
      ],
    },
    {
      id: 'borrado',
      number: '12',
      icon: 'trash-outline',
      title: 'How to delete your data',
      paragraphs: [
        'You have three paths and none of them requires a phone call. The fastest is the button on this screen, and those buttons really delete: they do not just hide anything.',
      ],
      bullets: [
        'From the app: wipes the device instantly and cancels your server-side bookings in one step. Ideal if the phone is not yours, or you simply want a clean start.',
        'By email: write to privacidad@seven.app with your device identifier and the booking code. We verify it is you and delete the record; if we are legally required to keep something, we tell you what and why.',
        'Verbal request: we accept those too. Just write to us and we delete the information attached to the code or the device identifier you give us.',
      ],
    },
    {
      id: 'seguridad',
      number: '13',
      icon: 'lock-closed-outline',
      title: 'Security',
      paragraphs: [
        'We apply reasonable technical and organisational measures to protect the information you give us. No system is infallible, but these are the practices we follow to look after your data from day one:',
      ],
      bullets: [
        'All traffic between the app and the server is encrypted with HTTPS/TLS.',
        'We store no passwords because there are no accounts or credentials to manage.',
        'The device identifier is not a credential: on its own it grants no access. Viewing or cancelling a booking requires both the code and that identifier.',
        'Internal access to booking records is restricted to the staff who need it to run the service.',
        'We do not store card data: the app does not process payments, you pay each provider directly.',
      ],
    },
    {
      id: 'menores',
      number: '14',
      icon: 'people-outline',
      title: 'Children',
      paragraphs: [
        'SEVEN is intended for people over 18. It is not a service aimed at children, and we do not knowingly gather data from minors. If you are a minor, please book with an adult.',
        'If you believe a minor gave us data, write to us and we will delete it immediately.',
      ],
    },
    {
      id: 'cambios',
      number: '15',
      icon: 'document-text-outline',
      title: 'Changes to this policy',
      paragraphs: [
        'We update this policy when the product or the law changes. The date at the top shows the current version. If a change materially affects your rights, we will announce it in the app before the new version takes effect.',
        'You can write to us for a copy of the version that was published when you made your booking.',
      ],
    },
    {
      id: 'contacto',
      number: '16',
      icon: 'mail-outline',
      title: 'Contact',
      paragraphs: [
        'Write to us at privacidad@seven.app. We answer in Spanish and English, Monday to Friday on San Andrés time. You can also reach us through the contact channels in the About section of the app.',
        'We take privacy seriously: if anything in this policy does not match what you experienced, we would rather know and fix it.',
      ],
      bullets: [
        'Email: privacidad@seven.app',
        'Supervision: Superintendencia de Industria y Comercio (SIC), Colombia.',
      ],
    },
  ],
};

const pt: PrivacyContent = {
  badge: 'Legal',
  title: 'Política de Privacidade',
  subtitle:
    'Quais dados a SEVEN toca, onde ficam, quem os vê e como apagá-los. Sem letra miúda: esta tela também deixa você apagá-los com um toque.',
  updatedLabel: 'Última atualização',
  updatedOn: '27 de setembro de 2026',
  intro:
    'A SEVEN é um app de turismo para San Andrés e as ilhas. Funciona sem contas, sem senhas e sem publicidade. Esta política explica, em português claro, cada dado que o app trata: o que fica no seu aparelho, o que chega aos nossos servidores e o que é compartilhado com terceiros. Se não está aqui, não coletamos.',
  onThisPage: 'Nesta página',
  highlightsTitle: 'Privacidade num relance',
  highlights: [
    {
      icon: 'person-circle-outline',
      title: 'Sem conta e sem login',
      text: 'Nunca pedimos e-mail, senha ou documento. Não existe perfil de usuário para rastrear.',
    },
    {
      icon: 'ban-outline',
      title: 'Sem anúncios e sem perfilamento',
      text: 'Sem SDK de anúncios, sem pixels e sem venda de dados. Não há o que usar para te perfilar.',
    },
    {
      icon: 'eye-outline',
      title: 'Sem análise oculta',
      text: 'Não rastreamos sessões nem eventos para marketing. Só os dados que uma reserva exige.',
    },
    {
      icon: 'trash-outline',
      title: 'Apagar em um toque',
      text: 'Você pode apagar tudo que o app sabe sobre você nesta própria tela.',
    },
  ],
  inventoryTitle: 'Seus dados neste aparelho',
  inventoryHint:
    'Esta é uma leitura real do que está salvo neste celular agora, não uma descrição genérica. Os valores abaixo são os seus dados.',
  inventoryEmpty: 'Nenhum dado salvo neste aparelho.',
  inventoryInstallId: 'Identificador do aparelho',
  inventoryFavorites: 'Lugares salvos',
  inventoryRecent: 'Vistos recentemente',
  inventoryBookings: 'Reservas salvas',
  inventoryGuest: 'Dados de contato para preencher',
  inventoryLanguage: 'Idioma do app',
  controlsTitle: 'Seus controles',
  controlsHint:
    'Escolha até onde quer ir. Dá para fazer isso e continuar usando o app normalmente: se você apagar o identificador do aparelho, suas reservas antigas deixam de aparecer nele.',
  actionDevice: 'Apagar os dados deste aparelho',
  actionDeviceHint:
    'Limpa idioma, preferências, salvos, histórico, dados de contato, reservas locais e notificações agendadas. Não toca o servidor.',
  actionEverything: 'Apagar tudo, incluindo minhas reservas',
  actionEverythingHint:
    'Primeiro cancela cada reserva no servidor e depois limpa o aparelho. Os fornecedores deixam de receber informações dessas solicitações.',
  actionEmail: 'Enviar uma solicitação de apagamento',
  actionEmailHint:
    'Abre seu e-mail com a solicitação já escrita e o identificador do aparelho incluído. Uma pessoa da equipe responde, normalmente em 15 dias úteis.',
  confirmDeviceTitle: 'Apagar os dados deste aparelho?',
  confirmDeviceBody:
    'Serão apagados o idioma, a preferência de animações, o identificador do aparelho, os lugares salvos, o histórico, seus dados de contato pré-preenchidos, as reservas locais e qualquer notificação agendada. Esta ação não pode ser desfeita.',
  confirmDeviceConfirm: 'Sim, apagar',
  confirmEverythingTitle: 'Apagar tudo e cancelar as reservas?',
  confirmEverythingBody:
    'Todas as reservas salvas neste aparelho serão canceladas no servidor e depois o celular será limpo. Se uma reserva já foi confirmada pelo fornecedor, cancele também com ele para evitar cobranças. Reservas já pagas ou consumidas são mantidas por prazo legal e são eliminadas quando ele termina.',
  confirmEverythingConfirm: 'Sim, apagar e cancelar',
  cancel: 'Cancelar',
  working: 'Apagando…',
  doneDevice: 'Pronto. Este aparelho não guarda mais nenhum dado seu.',
  doneEverything: 'Pronto. Aparelho limpo e {count} reserva(s) cancelada(s).',
  donePartial: 'Aparelho limpo, mas {failed} reserva(s) não puderam ser canceladas. Escreva para a gente concluir.',
  close: 'Entendi',
  footer:
    'SEVEN · San Andrés, Ilhas Colombianas, Colômbia. Esta política vale para o app e para este site; se alguma versão divergir, prevalece a versão em espanhol.',
  sections: [
    {
      id: 'identidad',
      number: '01',
      icon: 'business-outline',
      title: 'Quem é responsável pelos seus dados',
      paragraphs: [
        'O controlador dos dados é a SEVEN, a plataforma turística que publica este app, sediada em San Andrés, Ilhas Colombianas, Colômbia.',
        'Não há uma empresa sem rosto atrás de um cadastro: o app é o produto. Para qualquer assunto de privacidade, escreva para privacidad@seven.app e respondem as mesmas pessoas que operam o serviço.',
      ],
      bullets: [
        'Controlador: SEVEN (operadora do app e do catálogo).',
        'Contato de privacidade: privacidad@seven.app.',
        'Autoridade supervisora na Colômbia: Superintendencia de Industria y Comercio (SIC).',
      ],
    },
    {
      id: 'resumen',
      number: '02',
      icon: 'shield-checkmark-outline',
      title: 'Resumo: o que fazemos e o que não fazemos',
      paragraphs: [
        'A SEVEN foi pensada para coletar o mínimo. Você pode navegar pelo catálogo inteiro, ver restaurantes, pousadas e passeios e salvar favoritos sem deixar nenhum registro nos nossos servidores.',
        'A única coisa que sai do seu aparelho para nós é uma reserva que você decide fazer, mais o identificador técnico do aparelho necessário para essa reserva chegar até você.',
      ],
      bullets: [
        'Sem cadastro e sem login: não criamos perfis de usuário.',
        'Sem cookies de publicidade, pixels de rastreamento ou fingerprinting.',
        'Não vendemos, alugamos nem cedemos seus dados para fins comerciais.',
        'Sua atividade nunca é usada para publicidade comportamental.',
        'Nenhuma decisão automatizada gera efeitos jurídicos para você.',
      ],
    },
    {
      id: 'datos',
      number: '03',
      icon: 'clipboard-outline',
      title: 'Quais dados coletamos',
      paragraphs: [
        'A tabela resume cada item, onde ele fica, por que precisamos dele e qual é a base legal. As bases seguem o GDPR (art. 6) e a Lei 1581 de 2012 da Colômbia.',
      ],
      table: {
        columns: ['Dado', 'Onde fica', 'Para quê', 'Base legal'],
        rows: [
          [
            'Nome e telefone',
            'Servidor, e uma cópia no aparelho para preencher o formulário',
            'Atender a reserva e permitir que o fornecedor fale com você',
            'Execução de contrato / consentimento',
          ],
          [
            'E-mail (somente se você informar)',
            'Servidor',
            'Confirmações por e-mail quando o status muda',
            'Consentimento',
          ],
          [
            'Data, número de pessoas, tipo de experiência, valor e pedidos especiais',
            'Servidor',
            'Reservar, cobrar e entregar o serviço que você pediu',
            'Execução de contrato',
          ],
          [
            'Código da reserva e histórico de status',
            'Servidor',
            'Permitir acompanhar sua reserva e o fornecedor gerenciá-la',
            'Execução de contrato / legítimo interesse',
          ],
          [
            'Identificador do aparelho (install_id, UUID)',
            'Servidor',
            'Ligar uma reserva ao celular que a criou, sem precisar de conta nem e-mail',
            'Execução de contrato',
          ],
          [
            'Endereço IP, navegador e horário da requisição',
            'Registros do servidor (90 dias)',
            'Segurança, prevenção a abuso e diagnóstico de falhas',
            'Legítimo interesse',
          ],
        ],
      },
    },
    {
      id: 'dispositivo',
      number: '04',
      icon: 'phone-portrait-outline',
      title: 'O que fica só no seu aparelho',
      paragraphs: [
        'Nada disso sai do seu aparelho a não ser que você faça uma reserva. Você pode ver o que existe agora no inventário desta tela e apagar com um botão.',
      ],
      table: {
        columns: ['Chave', 'O que é', 'Por que existe'],
        rows: [
          ['seven_language', 'Idioma da interface (es, en, pt)', 'Para o app abrir no idioma que você escolheu'],
          ['seven_reduce_motion', 'Preferência de animações', 'Acessibilidade'],
          ['seven_welcome_completed', 'Marca de que você já viu a tela de boas-vindas', 'Não mostrar a introdução de novo'],
          ['seven_install_id', 'Identificador único desta instalação', 'Saber quais reservas são deste aparelho'],
          ['seven:guest', 'Nome e telefone digitados da última vez', 'Não repetir a cada reserva'],
          ['seven:favorites', 'Lugares que você salvou', 'Sua lista de desejos, só sua'],
          ['seven:recent', 'Últimos lugares vistos (máx. 20)', 'Reabrir rápido o que você estava vendo'],
          ['seven:bookings', 'Cópia local das reservas e dos códigos', 'Vê-las sem internet e receber avisos'],
        ],
      },
    },
    {
      id: 'permisos',
      number: '05',
      icon: 'navigate-outline',
      title: 'Permissões do aparelho',
      paragraphs: [
        'O app só pede permissões do sistema quando alguma função precisa delas, e cada permissão pode ser revogada nos ajustes do celular sem quebrar o app.',
      ],
      bullets: [
        'Localização: usada só quando você toca em “como chegar?” em um passeio, para estimar distância e tempo até o ponto de encontro. A coordenada é processada no aparelho e nunca vai para nossos servidores.',
        'Notificações: apenas no app instalado, para avisar que uma reserva foi confirmada, cancelada ou concluída. Sem push de terceiros e sem campanhas.',
        'Compartilhar: abre o painel de compartilhar do sistema quando você decide compartilhar um passeio. A decisão é sua e o conteúdo é escolhido pelo app ou por você.',
        'Vibração (háptica): retorno tátil ao tocar. Não coleta nem transmite nada.',
        'Rede: usada para saber se há conexão e mostrar o aviso de modo offline. Não identifica sua rede.',
      ],
    },
    {
      id: 'finalidad',
      number: '06',
      icon: 'flag-outline',
      title: 'Para que usamos seus dados',
      paragraphs: [
        'Não te perfílamos, não treinamos um modelo de publicidade com sua atividade e nunca falamos com você oferecendo promoções a não ser que você tenha reservado e deixado um contato.',
      ],
      bullets: [
        'Mostrar o catálogo da ilha: dados públicos de negócios, nunca pessoais.',
        'Criar e gerenciar sua reserva e avisar quando ela mudar.',
        'Guardar suas preferências e salvos no aparelho.',
        'Manter o app seguro e funcionando: registros e limites antiabuso.',
        'Cumprir obrigações legais quando aplicável.',
      ],
    },
    {
      id: 'terceros',
      number: '07',
      icon: 'git-network-outline',
      title: 'Terceiros que veem parte dos seus dados',
      paragraphs: [
        'A SEVEN não roda tudo na própria infraestrutura. Estes fornecedores recebem dados somente nos cenários descritos; nenhum recebe suas informações de reserva por nossa conta.',
      ],
      table: {
        columns: ['Terceiro', 'O que recebe', 'Quando'],
        rows: [
          [
            'Google Fonts',
            'Seu IP e agente de usuário ao carregar as fontes Oswald e Poppins',
            'Toda vez que você abre o app',
          ],
          [
            'WhatsApp (Meta)',
            'O que você digita e a mensagem com o código da reserva',
            'Quando você toca em um botão de WhatsApp para falar com um fornecedor',
          ],
          [
            'Instagram e Facebook (Meta)',
            'Sua atividade nessas plataformas',
            'Só se você abrir nossos perfis pelo app',
          ],
          [
            'Provedor de e-mail',
            'Nome, e-mail (se informado) e status da reserva',
            'Quando é enviada uma confirmação de status',
          ],
          [
            'Provedor de hospedagem e banco de dados',
            'Dados da reserva e identificador do aparelho',
            'Quando você reserva, enquanto o registro existir',
          ],
          [
            'Lojas de apps (Google Play, App Store)',
            'Metadados da instalação',
            'Quando você instala ou atualiza o app',
          ],
        ],
      },
      link: {
        label: 'Ver a política de privacidade do Google Fonts',
        href: 'https://policies.google.com/privacy',
      },
    },
    {
      id: 'ia',
      number: '08',
      icon: 'hardware-chip-outline',
      title: 'Transparência: este app foi feito com IA',
      paragraphs: [
        'Falamos claramente porque achamos o certo: o design, a interface, os textos e o código da SEVEN foram criados com assistência de inteligência artificial, sob direção e revisão humanas.',
        'O que a IA não faz neste produto: não há chatbot, nem motor de recomendações, nem análise de voz, rosto ou biometria, nem geração de conteúdo personalizado. Nenhum modelo de IA processa os dados pessoais de quem usa o app.',
        'Durante o desenvolvimento essas ferramentas nunca receberam dados reais de viajantes: o catálogo de exemplo e os depoimentos visíveis no app são fictícios e servem apenas como demonstração. O conteúdo que descreve negócios reais (fotos, preços, disponibilidade) é enviado e validado por cada fornecedor.',
        'Todo o conteúdo publicado passa por revisão humana antes de ficar visível. Se um dia incluirmos uma função de IA que processe dados pessoais, esta política será atualizada antes do lançamento e pediremos seu consentimento se a lei exigir.',
      ],
    },
    {
      id: 'cookies',
      number: '09',
      icon: 'server-outline',
      title: 'Cookies e armazenamento local',
      paragraphs: [
        'A SEVEN não usa cookies. Sem cookies de sessão, de publicidade ou de análise. O app funciona com armazenamento local do navegador ou do sistema (localStorage e IndexedDB), que só este app lê e que não consegue te acompanhar entre sites.',
        'Você pode limpar quando quiser por esta tela, pelos ajustes do celular ou apagando os dados do app.',
      ],
    },
    {
      id: 'conservacion',
      number: '10',
      icon: 'time-outline',
      title: 'Por quanto tempo guardamos os dados',
      paragraphs: [
        'Não guardamos dados por tempo indeterminado. Estes são os prazos, e nenhum deles depende de você lembrar de nos pedir algo:',
      ],
      bullets: [
        'No aparelho: até você apagar, limpar os dados do app ou desinstalar.',
        'Reservas: enquanto a reserva estiver ativa e por até 12 meses após ser concluída ou cancelada, para atender dúvidas e reclamações. Depois são apagadas ou anonimizadas de forma irreversível.',
        'Registros do servidor (IP, navegador): 90 dias.',
        'E-mails de confirmação: até 12 meses após a reserva.',
        'Cópias nos fornecedores: enquanto a reserva estiver ativa e por mais 30 dias após o encerramento.',
      ],
    },
    {
      id: 'derechos',
      number: '11',
      icon: 'checkmark-done-outline',
      title: 'Seus direitos',
      paragraphs: [
        'A Lei 1581 de 2012 (Colômbia) e o GDPR garantem, entre outros, os direitos abaixo. Para exercer qualquer um deles é só escrever para a gente: respondemos em até 15 dias úteis e não pedimos nada além do necessário para confirmar sua identidade.',
      ],
      bullets: [
        'Acesso: saber quais dados temos sobre você e receber uma cópia.',
        'Retificação: corrigir dados incorretos ou desatualizados.',
        'Apagamento: excluir seus dados quando já não houver motivo legítimo para guardá-los.',
        'Portabilidade: receber seus dados em formato estruturado e de uso comum.',
        'Revogação do consentimento: retirar o consentimento a qualquer momento, sem afetar a licitude do tratamento anterior.',
        'Oposição e limitação: se opor a um tratamento ou pedir que o limitemos.',
        'Não discriminação: exercer seus direitos nunca afeta negativamente como você é tratado.',
        'Reclamação: na SIC da Colômbia; se estiver na UE/EEE, na autoridade do seu país; na Califórnia, com o Attorney General sob a CCPA/CPRA.',
      ],
    },
    {
      id: 'borrado',
      number: '12',
      icon: 'trash-outline',
      title: 'Como apagar seus dados',
      paragraphs: [
        'Você tem três caminhos e nenhum exige ligação. O mais rápido é o botão desta tela, e esses botões apagam de verdade: não escondem a informação.',
      ],
      bullets: [
        'Pelo app: limpa o aparelho na hora e cancela suas reservas no servidor em um passo. Ideal se o celular não é seu, ou se você só quer recomeçar do zero.',
        'Por e-mail: escreva para privacidad@seven.app com seu identificador de aparelho e o código da reserva. Confirmamos que é você e apagamos o registro; se algo for obrigatório por lei, dizemos o quê e por quê.',
        'Pedido verbal: também aceitamos. É só escrever e apagamos as informações ligadas ao código ou ao identificador de aparelho que você nos passar.',
      ],
    },
    {
      id: 'seguridad',
      number: '13',
      icon: 'lock-closed-outline',
      title: 'Segurança',
      paragraphs: [
        'Aplicamos medidas técnicas e organizativas razoáveis para proteger as informações que você nos dá. Nenhum sistema é infalível, mas estas são as práticas com que cuidamos dos seus dados desde o primeiro dia:',
      ],
      bullets: [
        'Todo o tráfego entre o app e o servidor é criptografado com HTTPS/TLS.',
        'Não guardamos senhas porque não existem contas nem credenciais para gerenciar.',
        'O identificador do aparelho não é uma credencial: sozinho ele não dá acesso a nada. Para ver ou cancelar uma reserva são necessários o código e esse identificador.',
        'O acesso interno aos registros de reservas é restrito ao pessoal que precisa dele para operar o serviço.',
        'Não armazenamos dados de cartão: o app não processa pagamentos, o pagamento é feito direto com cada fornecedor.',
      ],
    },
    {
      id: 'menores',
      number: '14',
      icon: 'people-outline',
      title: 'Menores de idade',
      paragraphs: [
        'A SEVEN é destinada a pessoas com mais de 18 anos. Não é um serviço voltado a crianças e não coletamos conscientemente dados de menores. Se você for menor, faça a reserva com um adulto.',
        'Se achar que um menor nos passou dados, escreva para a gente e apagamos imediatamente.',
      ],
    },
    {
      id: 'cambios',
      number: '15',
      icon: 'document-text-outline',
      title: 'Mudanças nesta política',
      paragraphs: [
        'Atualizamos esta política quando o produto ou a lei mudam. A data no topo indica a versão vigente. Se a mudança afetar seus direitos de forma relevante, avisamos no app antes de a nova versão entrar em vigor.',
        'Você pode nos escrever para receber uma cópia da versão que estava publicada quando fez sua reserva.',
      ],
    },
    {
      id: 'contacto',
      number: '16',
      icon: 'mail-outline',
      title: 'Contato',
      paragraphs: [
        'Escreva para privacidad@seven.app. Atendemos em espanhol e inglês, de segunda a sexta no horário de San Andrés. Também dá para falar com a gente pelos canais de contato da seção Sobre do app.',
        'Levamos privacidade a sério: se algo desta política não bater com o que você viveu, preferimos saber e corrigir.',
      ],
      bullets: [
        'E-mail: privacidad@seven.app',
        'Supervisão: Superintendencia de Industria y Comercio (SIC), Colômbia.',
      ],
    },
  ],
};

export const PRIVACY_CONTENT: Record<Language, PrivacyContent> = { es, en, pt };
