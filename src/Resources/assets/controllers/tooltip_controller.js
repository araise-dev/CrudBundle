import { Controller } from '@hotwired/stimulus';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';

export default class extends Controller {
    static values = {
        title: String,
        ishtml: { type: Boolean, default: false },
        placement: { type: String, default: 'top' },
        interactive: { type: Boolean, default: false },
        offset: { type: Array, default:  [0, 10] },
    }

    connect() {
        tippy(this.element, {
            content: this.titleValue,
            allowHTML: this.ishtmlValue,
            placement: this.placementValue,
            interactive: this.interactiveValue,
            offset: this.offsetValue
        });
    }

    disconnect() {
        this.element._tippy.destroy()
    }
}
