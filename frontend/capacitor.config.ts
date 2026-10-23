import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.seven.app',
  appName: 'SEVEN',
  webDir: 'www',
  server: {
    androidScheme: 'https',
  },
  plugins: {
    SplashScreen: {
      // El splash lo retira la app, no el plugin: se esconde cuando el loader
      // global baja, que es el momento en que la web ya ha pintado. Con
      // `launchAutoHide` se tapaba a los 1200 ms haya arrancado Angular o no, y
      // en un móvil lento destapaba un webview en blanco. `AppComponent` lleva
      // un watchdog de 5 s por si no llegara nunca ese momento.
      launchShowDuration: 0,
      launchAutoHide: false,
      backgroundColor: '#0B2D4A',
      androidSplashResourceName: 'splash',
      androidScaleType: 'CENTER_CROP',
      showSpinner: false,
      splashFullScreen: false,
      splashImmersive: false,
    },
    StatusBar: {
      overlaysWebView: true,
      style: 'DARK',
      backgroundColor: '#0B2D4A',
    },
  },
};

export default config;
