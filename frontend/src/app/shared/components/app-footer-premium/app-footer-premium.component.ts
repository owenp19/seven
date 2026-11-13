import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-footer-premium',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './app-footer-premium.component.html',
  styleUrls: ['./app-footer-premium.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AppFooterPremiumComponent {
  year = new Date().getFullYear();
}
