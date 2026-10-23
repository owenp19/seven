import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { IonIcon } from '@ionic/angular';

/**
 * RatingStars
 *
 * Displays a 0–5 score as filled/half/empty star icons with an
 * optional review count label. Handles both integer and decimal
 * ratings (e.g. 4.5 renders a half star).
 */
@Component({
  selector: 'app-rating-stars',
  templateUrl: './rating-stars.component.html',
  styleUrls: ['./rating-stars.component.scss'],
  standalone: true,
  imports: [IonIcon],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RatingStarsComponent {
  @Input() rating = 0;
  @Input() count = 0;
  @Input() showCount = true;

  /** Array of 5 entries: 'full' | 'half' | 'empty'. */
  get stars(): Array<'full' | 'half' | 'empty'> {
    return Array.from({ length: 5 }, (_, index) => {
      const position = this.rating - index;
      if (position >= 0.75) {
        return 'full';
      }
      if (position >= 0.25) {
        return 'half';
      }
      return 'empty';
    });
  }
}