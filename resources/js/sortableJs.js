// Default SortableJS
import Sortable from 'sortablejs';

window.Sortable = Sortable;

const root = document.querySelector('[drag-root]');
// var items = document.getElementById('list');
new Sortable(root, {
    animation: 150,
    ghostClass: 'blue-background-class',
    onUpdate: function (evt) {
        // same properties as onEnd
        console.log(evt.newIndex);

        // Refresh the livewire component
        let component = Livewire.find(
            evt.target.closest('[wire\\:id]').getAttribute('wire:id')
        );
        console.log(component);

        // Order ids
        let orderIds = Array.from(root.querySelectorAll('[drag-item]'))
            .map(itemEl => itemEl.getAttribute('drag-item'));
        console.log(orderIds);

        // Method name
        let method = root.getAttribute('drag-root')

        // Call the livewire method
        component.call(method, orderIds);
    },
});

