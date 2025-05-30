import './bootstrap';
import 'flatpickr';

flatpickr('[data-datepicker]', {
    minDate: 'today',
    dateFormat: 'Y-m-d',
});