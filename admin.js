document.addEventListener("DOMContentLoaded", function () {
    const btnRegistroProveedores = document.getElementById("btn-registro-proveedores");
    const registroProveedores = document.getElementById("registro-proveedores");
    const listaProveedores = document.getElementById("lista-proveedores");
    const agregarProductos = document.getElementById("agregar-productos");
    const proveedoresLista = document.getElementById("proveedores-lista");
    const formProveedor = document.getElementById("form-proveedor");
    const formProducto = document.getElementById("form-producto");
    const btnAgregarColor = document.getElementById("btn-agregar-color");
    const listaColores = document.getElementById("lista-colores");

    let proveedores = [];
    let colores = [];

    // Mostrar formulario de registro de proveedores
    btnRegistroProveedores.addEventListener("click", function () {
        registroProveedores.style.display = "block";
        listaProveedores.style.display = "none";
        agregarProductos.style.display = "none";
    });

    // Registrar nuevo proveedor
    formProveedor.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const proveedor = {
            id: Date.now(),
            nombre: formData.get("nombre"),
            telefono: formData.get("telefono"),
            direccion: formData.get("direccion"),
            colonia: formData.get("colonia"),
            productos: [],
        };
        proveedores.push(proveedor);
        actualizarListaProveedores();
        this.reset();
        registroProveedores.style.display = "none";
        listaProveedores.style.display = "block";
    });

    // Agregar color a la lista
    btnAgregarColor.addEventListener("click", function () {
        const rgb = document.querySelector("input[name='color-rgb']").value;
        const titulo = document.querySelector("input[name='color-titulo']").value;
        if (rgb && titulo) {
            colores.push({ rgb, titulo });
            actualizarListaColores();
        }
    });

    // Agregar producto a un proveedor
    formProducto.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const producto = {
            nombre: formData.get("nombre"),
            precio: parseFloat(formData.get("precio")),
            tamaño: formData.get("tamaño"),
            colores: [...colores],
        };
        const proveedorId = parseInt(document.getElementById("proveedor-id").value);
        const proveedor = proveedores.find(p => p.id === proveedorId);
        if (proveedor) {
            proveedor.productos.push(producto);
            colores = []; // Reiniciar lista de colores
            actualizarListaColores();
            this.reset();
            agregarProductos.style.display = "none";
            listaProveedores.style.display = "block";
        }
    });

    // Actualizar lista de proveedores
    function actualizarListaProveedores() {
        proveedoresLista.innerHTML = proveedores.map(proveedor => `
            <li>
                <span>${proveedor.nombre}</span>
                <button onclick="mostrarFormularioProducto(${proveedor.id})">Agregar Producto</button>
            </li>
        `).join("");
    }

    // Mostrar formulario de productos
    window.mostrarFormularioProducto = function (proveedorId) {
        document.getElementById("proveedor-id").value = proveedorId;
        agregarProductos.style.display = "block";
        listaProveedores.style.display = "none";
    };

    // Actualizar lista de colores
    function actualizarListaColores() {
        listaColores.innerHTML = colores.map(color => `
            <div class="color-item">
                <div class="color-box" style="background-color: rgb(${color.rgb});"></div>
                <span>${color.titulo} (${color.rgb})</span>
            </div>
        `).join("");
    }
});