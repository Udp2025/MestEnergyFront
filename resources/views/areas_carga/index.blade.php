@extends('layouts.app')

@section('title', 'Energy & Cost Heatmap')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/images/login-image.jpg">
    <title>Production Line UI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            background: #f4f7f9;
            height: 100vh;
        }

        .content {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            padding: 30px;
            flex-grow: 1;
        }

        .canvas {
            display: flex;
            gap: 30px;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
        }

        .box {
            width: 220px;
            min-height: 150px;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 0 10px;
            border: 1px solid #ddd;
        }

        .add {
            background: #e9f7fd;
        }

        .subtract {
            background: #f8d7da;
        }

        .line {
            position: absolute;
            width: 2px;
            background-color: #000;
            height: 200px;
            top: 50%;
            left: 50%;
            transform: translateX(-50%) translateY(-50%);
            z-index: -1;
        }

        .droppable {
            width: 100%;
            min-height: 120px;
            border: 2px dashed #ddd;
            margin-top: 15px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            overflow-y: auto;
        }

        .sidebar {
            width: 280px;
            background: #ffffff;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: 100vh;
            overflow-y: auto;
            border-radius: 10px;
        }

        .sidebar h2 {
            text-align: center;
            color: #333;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .category {
            font-weight: bold;
            padding: 12px;
            background: #343a40;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 12px;
        }

        .category:hover {
            background: #495057;
        }

        .category-content {
            display: none;
            padding-left: 20px;
            padding-top: 10px;
        }

        .category.open .category-content {
            display: block;
        }

        .device {
            background: #007bff;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            color: white;
            font-size: 14px;
            transition: background 0.3s;
            margin-bottom: 8px;
        }

        .device:hover {
            background: #0056b3;
        }

        .device:active {
            cursor: grabbing;
        }

        .add-btn {
            margin-top: 20px;
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            display: block;
            width: 100%;
        }

        .add-btn:hover {
            background-color: #218838;
        }

        .remove-btn {
            cursor: pointer;
            color: #dc3545;
            font-size: 16px;
            margin-left: 10px;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        
  .verdebg{
    background-color: green;
  }
    </style>
</head>
<body>
    <div class="content">
        <div class="canvas">
            <div class="box add" id="addBox">
                <strong>Add</strong>
                <div class="droppable" id="addDroppable" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
                <i class="fa fa-plus add-icon" onclick="addOptions('addBox')"></i>
            </div>
            <div class="box subtract" id="subtractBox">
                <strong>Subtract</strong>
                <div class="droppable" id="subtractDroppable" ondrop="drop(event)" ondragover="allowDrop(event)"></div>
                <i class="fa fa-minus remove-icon" onclick="addOptions('subtractBox')"></i>
            </div>
        </div>

        <div class="sidebar">
            <h2>Koenig Line</h2>
            <div class="category" onclick="toggleCategory(this)">Mains</div>
            <div class="category-content">
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="T1 Supply">T1 Supply</div>
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="T2 Supply">T2 Supply</div>
            </div>

            <div class="category" onclick="toggleCategory(this)">Sub-mains</div>
            <div class="category-content">
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="Main Bus">Main Bus</div>
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="Sub Panel">Sub Panel</div>
            </div>

            <div class="category" onclick="toggleCategory(this)">Generation</div>
            <div class="category-content">
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="Caustic Recovery Battery">Caustic Recovery Battery</div>
                <div class="device" draggable="true" ondragstart="drag(event)" data-option="Fuel Cell Panel">Fuel Cell Panel</div>
            </div>
        </div>
    </div>

    <script>
        // Function to toggle the visibility of the category content
        function toggleCategory(category) {
            const content = category.nextElementSibling;
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
        }

        // Dragging functionality
        function drag(event) {
            event.dataTransfer.setData("text", event.target.dataset.option);
        }

        // Allow dropping functionality
        function allowDrop(event) {
            event.preventDefault();
        }

        // Dropping functionality
        function drop(event) {
            event.preventDefault();
            const data = event.dataTransfer.getData("text");
            const newElement = document.createElement('div');
            newElement.classList.add('option');
            newElement.innerHTML = data + '<i class="fa fa-times remove-btn" onclick="removeOption(this)"></i>';
            event.target.appendChild(newElement);
        }

        // Remove option functionality
        function removeOption(button) {
            button.parentElement.remove();
        }

        // Function to handle adding options (if needed)
        function addOptions(boxId) {
            // Logic for handling options adding (optional, depending on your needs)
        }
    </script>
</body>
</html>

@endsection
