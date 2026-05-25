import { startStimulusApp } from '@symfony/stimulus-bridge';
import './styles/app.scss';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
