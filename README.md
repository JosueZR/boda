# 💍 Página de Boda - Luis & Erendira

¡Bienvenido! Esta es tu página personalizada para la boda de Luis y Erendira con confirmación de asistencia.

## 📁 Archivos Incluidos

- **index.html** - Página principal (estructura HTML)
- **style.css** - Estilos y diseño responsivo
- **script.js** - Interactividad y funcionalidad
- **imagenes/** - Carpeta para almacenar las fotos de la galería
- **admin/** - Carpeta para archivos administrativos

## 🎨 Características

✅ **Sección de Bienvenida** - Encabezado con los nombres Luis & Erendira  
✅ **Formulario de RSVP** - Confirmación de asistencia con validación  
✅ **Galería de Fotos** - Con efecto hover y animaciones suaves  
✅ **Frase Romántica** - Sección inspiradora entre secciones  
✅ **Sección de Regalos** - Mensaje personalizado sobre regalos  
✅ **Diseño Responsivo** - Se adapta a celulares, tablets y desktop  

## 📝 Cómo Personalizar

### 1. Agregar Fotos a la Galería

Coloca tus fotos en la carpeta `imagenes/` con los nombres:
- `foto1.jpg`
- `foto2.jpg`
- `foto3.jpg`
- `foto4.jpg`
- `foto5.jpg`
- `foto6.jpg`

Las fotos se mostrarán automáticamente en la galería.

### 2. Cambiar la Frase Romántica

En `index.html`, busca la sección **quote-section** y reemplaza el texto:

```html
<p class="quote-text">"Tu nueva frase aquí"</p>
<p class="quote-author">- Luis & Erendira</p>
```

### 3. Personalizar Colores

En `style.css`, modifica las variables al inicio del archivo:

```css
:root {
    --primary-color: #d4577b;      /* Color principal (rosa) */
    --secondary-color: #f4e4d7;    /* Color secundario (crema) */
    --dark-color: #2c2c2c;         /* Texto oscuro */
    --light-color: #f9f9f9;        /* Fondo claro */
    --accent-color: #c99db8;       /* Color de acento */
}
```

### 4. Configurar el Formulario RSVP

El formulario recopila:
- Nombre
- Correo
- Teléfono
- Confirmación de asistencia
- Número de acompañantes
- Mensaje personal

Para procesar los datos, necesitarás:
- Un backend (servidor)
- O integración con un servicio como Formspree, Netlify Forms, etc.

## 🚀 Cómo Usar

1. Abre `index.html` en tu navegador
2. La página se mostrará con todas las secciones
3. Al hacer scroll, verás las animaciones suaves
4. El formulario de RSVP mostrará un mensaje de confirmación al enviar

## 📱 Características Responsivas

- **Desktop**: Diseño de 3 columnas en galería y regalos
- **Tablet**: Diseño de 2 columnas
- **Móvil**: Diseño de 1 columna, optimizado para pantalla pequeña

## 🎯 Secciones de la Página

### 1. **Hero Section**
Encabezado con gradiente bonito y los nombres de los novios.

### 2. **RSVP Section**
Formulario elegante para confirmar asistencia con validaciones.

### 3. **Quote Section**
Sección inspiradora con frase romántica sobre fondo degradado.

### 4. **Gallery Section**
Galería de fotos con efecto hover y overlay de texto.

### 5. **Gifts Section**
Explicación personalizada sobre regalos:
- **Mensaje Principal**: "Con tu asistencia es nuestro mayor regalo"
- **Alternativas**: Contribución monetaria, regalos especiales, experiencias

### 6. **Footer**
Links de navegación rápida y créditos.

## 💡 Tips

- Usa fotos de buena calidad (mínimo 400x400px)
- Los nombres Luis & Erendira están en toda la página
- El mensaje de regalos está personalizado como pediste
- Los colores son románticos y elegantes
- La página es 100% responsiva

## 🔧 Próximas Mejoras (Opcional)

- Integrar formulario RSVP con base de datos
- Agregar mapa de ubicación de la boda
- Agregar video o slideshow en la galería
- Agregar countdown timer para la boda
- Crear apartado de hotel/transporte

## 📧 Soporte

Si necesitas cambios adicionales, puedo ayudarte a personalizar cualquier sección.

¡Que disfrutes tu gran día! 💕

---
Hecho con ❤️ por GitHub Copilot
