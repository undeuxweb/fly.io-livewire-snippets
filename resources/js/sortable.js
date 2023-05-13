const listItems = document.querySelectorAll('[drag-item]'); // Get the list item elements
const listHidden = document.querySelector('.list-hidden'); // Get the hidden element
const list = document.querySelector('[drag-root]'); // Get the list element

// Calculate the offset of the mouse pointer relative to the target element
const getMouseOffset = (evt) => {
    const targetRect = evt.target.getBoundingClientRect();
    const offset = {
        x: evt.pageX - targetRect.left,
        y: evt.pageY - targetRect.top
    };
    return offset;
};

// Get the vertical center of an element
const getElementVerticalCenter = (el) => {
    const rect = el.getBoundingClientRect();
    return (rect.bottom - rect.top) / 2;
};

// Insert a placeholder element during dragging to determine the drop position
const appendPlaceholder = (evt, idx) => {
    evt.preventDefault();
    if (idx === dragIndex) {
        return;
    }

    const offset = getMouseOffset(evt);
    const middleY = getElementVerticalCenter(evt.target);
    const placeholder = list.children[dragIndex];

    // Insert the placeholder
    if (offset.y > middleY) {
        list.insertBefore(evt.target, placeholder); // Insert the dragging element below the placeholder
    } else if (list.children[idx + 1]) {
        list.insertBefore(evt.target.nextSibling || evt.target, placeholder); // Insert the dragging element above the placeholder
    }
    return;
};

// Initialize the sortable functionality
const sortable = (rootEl, onUpdate) => {
    let dragEl;

    // Make all list items draggable
    Array.from(rootEl.children).forEach((itemEl) => {
        itemEl.draggable = true;
    });

    // Handle the dragover event during sorting
    const _onDragOver = (evt) => {
        evt.preventDefault();
        evt.dataTransfer.dropEffect = 'move';

        const target = evt.target;
        if (target && target !== dragEl && target.nodeName == 'DIV') {
            const offset = getMouseOffset(evt);
            const middleY = getElementVerticalCenter(evt.target);

            // Sorting
            if (offset.y > middleY) {
                rootEl.insertBefore(dragEl, target.nextSibling); // Insert the dragging element below the target
            } else {
                rootEl.insertBefore(dragEl, target); // Insert the dragging element above the target
            }
        }
    };

    // Handle the dragend event after sorting ends
    const _onDragEnd = (evt) => {
        evt.preventDefault();

        dragEl.classList.remove('ghost');
        rootEl.removeEventListener('dragover', _onDragOver, false);
        rootEl.removeEventListener('dragend', _onDragEnd, false);

        onUpdate(dragEl); // Perform actions after sorting ends
    };

    // Handle the dragstart event to start sorting
    rootEl.addEventListener('dragstart', (evt) => {
        dragEl = evt.target; // Remember the element being dragged

        evt.dataTransfer.effectAllowed = 'move';
        evt.dataTransfer.setData('Text', dragEl.textContent);

        rootEl.addEventListener('dragover', _onDragOver, false);
        rootEl.addEventListener('dragend', _onDragEnd, false);

        setTimeout(() => {
            dragEl.classList.add('ghost');
        }, 0);
    }, false);
};

// Initialize the sortable functionality
sortable(list, (item) => {
    // Handle the sorted item
    console.log(item);

    // Get the order of the list items
    let orderIds = Array.from(document.querySelectorAll('[drag-item]'))
        .map(itemEl => itemEl.getAttribute('drag-item'));
    console.log(orderIds);
});


