document.querySelectorAll('.category').forEach(category => {
    category.addEventListener('click', () => {
        category.classList.toggle('open');
    });
});

const devices = document.querySelectorAll('.device');
const droppables = document.querySelectorAll('.droppable');

devices.forEach(device => {
    device.addEventListener('dragstart', e => {
        e.dataTransfer.setData('text', e.target.innerText);
    });
});

droppables.forEach(dropZone => {
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
    });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        const data = e.dataTransfer.getData('text');
        const newElement = document.createElement('div');
        newElement.classList.add('device');
        newElement.innerText = data;
        newElement.draggable = true;
        newElement.addEventListener('dragstart', dragStart);
        e.target.appendChild(newElement);
    });
});

function dragStart(e) {
    e.dataTransfer.setData('text', e.target.innerText);
}
 