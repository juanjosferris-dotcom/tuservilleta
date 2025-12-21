# tuservilleta

Plugin de WordPress para personalizar servilletas y posavasos con un flujo guiado de 6 pasos (Tamaño, Calidad, Color, Cantidad, Impresión e Imagen). Calcula el precio a partir de un catálogo en CSV, permite enviar la solicitud a la empresa y pagar directamente con PayPal/tarjeta, además de estar preparado para formularios de HubSpot.

## Instalación rápida
1. Sube la carpeta `tuservilleta-personalizador` como plugin o instala el ZIP generado (`tuservilleta-personalizador.zip`).
2. Activa el plugin en WordPress.
3. En **Ajustes → Tu Servilleta** encontrarás pestañas:
   - **Catálogo**: sube el CSV `;` con columnas `size;type;color;printing;quantity;price` (detecta cabecera y muestra solo opciones existentes).
   - **Pagos**: correo de contacto, PayPal Business, moneda, HubSpot, y opcionalmente un enlace de Stripe Payment Link/Checkout (`{amount}` se sustituye por el importe).
   - **Imágenes de pasos**: sube imágenes para cada paso (aparecen en las tarjetas de selección).

## Uso en el front
Añade el shortcode en una página o entrada:

```
[tuservilleta_customizer]
```

Los pasos se muestran como tarjetas (con imágenes si se cargaron) y solo enseñan las combinaciones disponibles en el CSV. Al hacer clic se avanza automáticamente. El presupuesto final ofrece envío a la empresa (con adjunto opcional de imagen) o pago inmediato (Stripe si hay enlace configurado, si no PayPal/tarjeta invitado).

## Datos requeridos
- **CSV**: `size;type;color;printing;quantity;price` (punto o coma para decimales).
- **HubSpot** (opcional): portalId y formId para incrustar el formulario.
- **Pagos** (opcional): email de PayPal Business y moneda (por defecto EUR); enlace de Stripe Payment Link/Checkout con `{amount}` si se quiere Stripe.
- **Imágenes** (opcional): una por paso para mostrar en las tarjetas.

## Empaquetado
El ZIP listo para subir se genera como `tuservilleta-personalizador.zip` en la raíz del repositorio.
