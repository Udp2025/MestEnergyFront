@extends('layouts.app')

@section('title', 'Agregar')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Line UI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/agregar.css') }}">

</head>
<body>
    <div class="content">
        <div class="canvas">
            <div class="box add">
                <strong>Add</strong>
                <div class="droppable"></div>
            </div>
            <div class="group-total">Group Total</div>
            <div class="box subtract">
                <strong>Subtract</strong>
                <div class="droppable"></div>
            </div>
        </div>
    </div>
    <div class="sidebar">
        <h2>Koenig Line</h2>
        <div class="category">Mains</div>
        <div class="category-content">
            <div class="device" draggable="true">T1 Supply</div>
            <div class="device" draggable="true">T2 Supply</div>
        </div>
        <div class="category">Sub-mains</div>
        <div class="category-content">
            <div class="device" draggable="true">Main Bus</div>
            <div class="device" draggable="true">Sub Panel</div>
        </div>
        <div class="category">Generation</div>
        <div class="category-content">
            <div class="device" draggable="true">Caustic Recovery Battery</div>
            <div class="device" draggable="true">Fuel Cell Panel</div>
        </div>
    </div>
    <script>
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
    </script>
</body>
</html>
@endsection
