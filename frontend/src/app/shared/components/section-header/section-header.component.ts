import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IonIcon } from '@ionic/angular';

/**
 * SectionHeader
 *
 * Consistent title/subtitle block used at the top of lists and home
 * sections. Optional "see all" link navigates to the section path.
 */
@Component({
  selector: 'app-section-header',
  templateUrl: './section-header.component.html',
  styleUrls: ['./section-header.component.scss'],
  standalone: true,
  imports: [IonIcon, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SectionHeaderComponent {
  @Input() title = '';
  @Input() subtitle = '';
  @Input() linkLabel = '';
  @Input() linkUrl: string | null = null;
}