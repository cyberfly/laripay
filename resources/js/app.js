import 'trix';
import 'trix/dist/trix.css';

import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);
window.Chart = Chart;
