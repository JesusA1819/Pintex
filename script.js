// Agregar nueva pared a la tabla
document.getElementById('add-wall').addEventListener('click', function() {
    const wallContainer = document.getElementById('wall-container');

    const newRow = document.createElement('tr');
    newRow.classList.add('wall');

    newRow.innerHTML = `
        <td><input type="number" placeholder="Ancho" name="width[]" required></td>
        <td><input type="number" placeholder="Alto" name="height[]" required></td>
        <td><button type="button" class="remove-wall">Eliminar</button></td>
    `;

    // Agregar la nueva fila a la tabla
    wallContainer.appendChild(newRow);

    // Agregar funcionalidad para eliminar una pared
    newRow.querySelector('.remove-wall').addEventListener('click', function() {
        newRow.remove();
    });
});

// Calcular área
document.getElementById('calculate-area').addEventListener('click', function() {
    const walls = document.querySelectorAll('.wall');
    let totalArea = 0;

    walls.forEach(function(wall) {
        const width = parseFloat(wall.querySelector('input[name="width[]"]').value);
        const height = parseFloat(wall.querySelector('input[name="height[]"]').value);
        if (width && height) {
            totalArea += width * height;
        }
    });

    document.getElementById('total-area').textContent = `Área total: ${totalArea} m²`;
});

// Calcular pintura
document.getElementById('calculate-paint').addEventListener('click', function() {
    const totalAreaText = document.getElementById('total-area').textContent;
    const totalArea = parseFloat(totalAreaText.split(': ')[1].split(' ')[0]);

    if (isNaN(totalArea) {
        alert('Por favor, calcule primero el área.');
        return;
    }

    // Mostrar un cuadro de diálogo para seleccionar el tipo de pintura y superficie
    const tipoPintura = prompt("Seleccione el tipo de pintura (interior/exterior):");
    const tipoSuperficie = prompt("Seleccione el tipo de superficie (lisa/nueva/porosa):");

    if (!tipoPintura || !tipoSuperficie) {
        alert('Debe seleccionar el tipo de pintura y superficie.');
        return;
    }

    // Valores de rendimiento predeterminados (m²/litro)
    const rendimientos = {
        interior: 10, // Rendimiento para pintura de interior
        exterior: 8   // Rendimiento para pintura de exterior
    };

    // Factores de ajuste según el tipo de superficie
    const factoresSuperficie = {
        lisa: 1,
        nueva: 1.1,
        porosa: 1.3
    };

    // Obtener el rendimiento y el factor de superficie
    const rendimiento = rendimientos[tipoPintura.toLowerCase()];
    const factorSuperficie = factoresSuperficie[tipoSuperficie.toLowerCase()];

    if (!rendimiento || !factorSuperficie) {
        alert('Tipo de pintura o superficie no válido.');
        return;
    }

    // Calcular la cantidad de pintura necesaria
    const litrosNecesarios = (totalArea / rendimiento) * factorSuperficie;

    // Mostrar el resultado
    document.getElementById('paint-needed').textContent = `Cantidad de pintura necesaria: ${litrosNecesarios.toFixed(2)} litros`;
});