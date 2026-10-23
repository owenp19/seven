import { Injectable } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';

/**
 * HapticsService
 *
 * Cross-platform haptic feedback. On native (Capacitor) it uses the real
 * iOS/Android haptic engine; on the web it falls back to the standard
 * navigator.vibrate() API so the app never breaks in a browser.
 */
@Injectable({ providedIn: 'root' })
export class HapticsService {
  /** Light tap — selected tabs, small controls. */
  async selection(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
      await Haptics.selectionStart();
      await Haptics.selectionEnd();
    } else {
      this.webVibrate(10);
    }
  }

  /** Medium impact — primary buttons, cards. */
  async light(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
      await Haptics.impact({ style: ImpactStyle.Light });
    } else {
      this.webVibrate(15);
    }
  }

  /** Stronger impact — destructive or key actions. */
  async medium(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
      await Haptics.impact({ style: ImpactStyle.Medium });
    } else {
      this.webVibrate(25);
    }
  }

  /** Success feedback — completed booking, etc. */
  async success(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
      await Haptics.notification({ type: NotificationType.Success });
    } else {
      this.webVibrate([10, 30, 10]);
    }
  }

  /** Warning feedback. */
  async warning(): Promise<void> {
    if (Capacitor.isNativePlatform()) {
      await Haptics.notification({ type: NotificationType.Warning });
    } else {
      this.webVibrate([20, 40, 20]);
    }
  }

  private webVibrate(pattern: number | number[]): void {
    if ('vibrate' in navigator) {
      navigator.vibrate(pattern);
    }
  }
}
