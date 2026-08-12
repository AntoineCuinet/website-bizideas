import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['preview'];
    static values = {
        weights: Object
    };

    connect() {
        this.update();
    }

    update() {
        // Query all radio inputs in the form
        const radios = this.element.querySelectorAll('input[type="radio"]');
        const criteriaScores = {};

        radios.forEach((radio) => {
            if (radio.checked) {
                // Read from data-criterion, or parse the field name (e.g. business_idea[rating_profitability])
                let criterion = radio.getAttribute('data-criterion');
                if (!criterion && radio.name) {
                    const match = radio.name.match(/rating_([a-z_]+)/);
                    if (match) {
                        criterion = match[1];
                    }
                }

                if (criterion) {
                    criteriaScores[criterion] = parseInt(radio.value, 10);
                }
            }
        });

        let totalWeight = 0;
        let weightedSum = 0;

        // Safely retrieve weights object (fallback if parsed as empty array [] instead of object {})
        const weightsObj = (this.hasWeightsValue && !Array.isArray(this.weightsValue)) ? this.weightsValue : {};

        Object.entries(criteriaScores).forEach(([criterion, score]) => {
            const weightStr = weightsObj[criterion] || 'medium';
            const weightVal = this.getWeightValue(weightStr);
            
            weightedSum += score * weightVal;
            totalWeight += weightVal;
        });

        if (totalWeight === 0) {
            this.previewTarget.textContent = '-';
        } else {
            const average = weightedSum / totalWeight;
            this.previewTarget.textContent = average.toFixed(2) + ' / 5';
        }
    }

    getWeightValue(weight) {
        switch (weight) {
            case 'low': return 1.0;
            case 'medium': return 2.0;
            case 'high': return 3.0;
            default: return 2.0;
        }
    }
}
