# Copy Embed Link

**Copia un enlace directo para embeber cualquier archivo de tu Nextcloud** — un enlace que termina en el nombre real del archivo con su extensión, para que el archivo se reproduzca o se vea *dentro* de otras aplicaciones en vez de mostrar una página de descarga.

[English version](README.md)

## Por qué

El compartir de Nextcloud es excelente para enviar archivos a personas, pero sus enlaces no sirven como embebidos: los editores de notas y páginas (AppFlowy, Notion, Obsidian, WordPress…) necesitan un enlace directo al archivo mismo. Copy Embed Link agrega el botón que falta en el menú de compartir de Nextcloud: **Copy embed link**.

## Qué hace

- **Un archivo, un enlace.** Compartir un solo archivo expone solo ese archivo — nunca la carpeta que lo rodea.
- **Enlace raíz.** El enlace va amarrado a la identidad interna del archivo, no a su ruta, así que sigue funcionando si mueves el archivo de carpeta o le cambias el nombre. Solo muere si el archivo se elimina.
- **Nombre real.** El enlace termina en el nombre y la extensión verdaderos (`…/DRILL.mp3`), que es lo que los reproductores de otras apps exigen.
- **Control de acceso.** Los enlaces pueden ser públicos o protegidos con contraseña, y pueden tener fecha de vencimiento.
- **Carpeta con selección.** Comparte una carpeta y elige exactamente qué archivos se ven a través del enlace; el resto queda oculto.
- **Un solo panel para todo.** Una página de ajustes lista todos los enlaces que has creado, con su acceso y vencimiento, listos para editar o revocar en cualquier momento.
- **Sin invadir.** Si quitas la app, Nextcloud queda exactamente como estaba. No se modifica nada del corazón de Nextcloud.

## Estado

🚧 **En desarrollo** — la v0.1.0 es el esqueleto del proyecto (ficha de la app y arranque). El motor de enlaces viene después.

## Autor

Creado y mantenido por **Pulgarin93** — una primera contribución open source, construida a la vista de todos, proceso incluido.

## Licencia

AGPL-3.0 o posterior. Libre para usar, copiar y mejorar; las mejoras se comparten bajo la misma licencia.
