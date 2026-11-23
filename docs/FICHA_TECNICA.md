# FICHA TÉCNICA — Copy Embed Link (documento de trabajo, ES)

Proyecto: primera contribución open source de Pulgarin93.
Nombre: **Copy Embed Link** · App ID: `copy_embed_link` · Repo: github.com/Pulgarin93/copy-embed-link
Idioma oficial: inglés (botón, interfaz, tienda). GitHub bilingüe EN+ES.
Licencia: AGPL-3.0 o posterior. Tienda de Nextcloud: gratis, sin pagos; donaciones opcionales (no por ahora).
Éxito medido en: descargas.

## Qué se está creando (texto de Pulgarin, organizado por Kai — APROBADO)

Un botón dentro de Nextcloud para extraer o crear enlaces a los archivos y carpetas que viven en
Nextcloud — que además sirve de interfaz de un servidor (todo lo que Nextcloud ve por sus montajes).
El enlace sirve para embeber (insertar y reproducir dentro de otra página): AppFlowy hoy; su web o
Notion mañana. Si un día se quita la app, Nextcloud queda como estaba: no se toca el corazón de
Nextcloud ni el de AppFlowy (que ya acepta estos enlaces — probado).

## Funciones y objetivo (de Pulgarin)

- Compartir un archivo o una carpeta (png, jpg, pdf, txt, mp3, mp4, wav, etc.).
- Permisos por selección: carpeta con 30 archivos, se comparten 8 → solo se ven los 8 autorizados.
  (Kai: se construye como lista exacta de archivos bajo ese enlace; el resultado es el pedido.)
- Enlaces públicos o privados: invitados por correo y contraseña, o claves de acceso.
- Embeber en Notion, AppFlowy, Obsidian y WordPress.
- Enlace reutilizable, sin límite de personas, no se pierde.
- Panel de configuración: lista de TODOS los enlaces compartidos, con quién, permisos,
  modificar permisos, fecha de vencimiento/expiración.

## Reconocimiento de tipo de archivo (ampliación de Pulgarin, 2026-11-11) — OBLIGATORIO

El botón RECONOCE el archivo que se está compartiendo y de eso depende el enlace que entrega:

- **Embebible** (mp3, wav, mp4, jpg, png, pdf, txt…): enlace de embebido — termina en el nombre
  real con su extensión y el archivo se reproduce o se ve dentro de la otra app.
- **Compartible pero NO embebible** (zip y comprimidos, proyectos de diseño/Adobe — psd, ai,
  prproj, aep —, archivos de plataformas sin reproductor): enlace de compartir (ver/descargar),
  con los mismos controles (contraseña, vencimiento). La interfaz DICE claramente qué tipo de
  enlace entregó y por qué: nadie debe esperar un reproductor donde no puede haberlo.
- **¿Original o en formato X?** (idea de Pulgarin): cuando un archivo admite más de un formato
  útil, el botón pregunta cuál compartir: el original o el convertido.
  Nota de Kai (a validar con Pulgarin): convertir es un motor aparte — su ecosistema ya tiene
  FileFlows para conversiones; fase 1 = reconocer y elegir el enlace correcto; la conversión
  ofrecida desde el botón queda como fase 2 anotada, no descartada.

## Permisos: carpeta vs enlace (Pulgarin, 2026-11-11) — OBLIGATORIO

Regla madre: el permiso de la carpeta y el permiso del enlace son INDEPENDIENTES;
manda el más específico (el del enlace/archivo sobre el de la carpeta). Casos de Pulgarin:

1. **Carpeta privada + compartir UN archivo de adentro** → el enlace hace alcanzable SOLO
   ese archivo, con el nivel que el dueño elija (solo ver / solo lectura, o también descargar).
   La carpeta sigue privada: nadie ve su nombre de contenido ni qué más hay adentro.
2. **Carpeta pública + candado en UN archivo** → al compartir la carpeta, el archivo con
   candado NO sale: su candado manda sobre lo público de la carpeta.
3. **La pregunta obligatoria (guardia):** cuando el compartir cruza la frontera de la carpeta
   (sacar un archivo de una carpeta privada), el botón pregunta al dueño en palabras simples
   ANTES de crear el enlace — en el inglés oficial de la app:
   «This folder is private. Share just this file?» —
   para que cada quien comparta desde sus carpetas tal como están,
   **sin crear copias del archivo en otra carpeta** (principio global de Pulgarin: nada duplicado).
4. Cada enlace define su propio nivel: solo ver (lectura) o ver + descargar; con contraseña
   y vencimiento opcionales, como ya estaba especificado.

## Pregunta de Pulgarin → respuesta de Kai (firmado)

¿Enlace raíz? SÍ: cada archivo tiene un número interno de identidad que no cambia al moverlo
de carpeta ni al renombrarlo. El enlace se amarra a ese número, no a la ruta; sobrevive a
movimientos y renombres. Solo muere si el archivo se elimina.

## Reglas fijadas

- Requisito de diseño #1 (Pulgarin): compartir UN archivo expone SOLO ese archivo;
  compartir una carpeta expone la carpeta (salvo la selección autorizada).
- Nada público revela su infraestructura (sin palabras VPS/NAS/servidores en lo público).
- La prueba que vale es la de Pulgarin en SUS apps, no demos de Kai.

## Diseño visual — APROBADO por Pulgarin ("está como lo imaginaba", 2026-11-11)

1. Lista de archivos con el menú abierto: «Copy embed link» como un renglón más.
2. Panel Sharing con sección «Embed link»: enlace terminado en el nombre real, botón Copy,
   interruptor de contraseña y fecha de vencimiento.
3. Ventana de carpeta con checks: «5 of 9 files will be visible».
4. Ajustes → «Embed links»: tabla con todos los enlaces (File, Link, Access, Expires, Edit, Revoke).
5. Resultado: el enlace sonando dentro de AppFlowy (audio 0:47/2:16 + video).
Archivos: ~/workspace/imagine_media/media-generation-copy-embed-link-mockup-{1..5}-*.webp

## Ruta de publicación (verificada en docs oficiales, 2026-11-11)

1. Repo en GitHub (hecho en v0.1.0) y estreno de la lista «Mis aportes».
2. Instalar en su Nextcloud vía Dexter y prueba de Pulgarin en sus apps.
3. Tienda de Nextcloud: cuenta de desarrollador → certificado/sello de autor (petición por
   pull request al repo de certificados) → registrar el app ID → subir cada versión como
   tar.gz firmado con su info.xml → automatizable con la acción oficial de GitHub.
   Reglas: sin «Nextcloud» en el nombre; licencia AGPL o compatible; la tienda no procesa pagos.

## Cómo está creada

PHP y JavaScript, el idioma de las apps de Nextcloud.

## v0.3.0 — la pieza nueva: el botón se compila (2026-11-22)

**Qué pasó:** en Nextcloud 33 el botón no aparecía. El script viejo
(`js/copy_embed_link-files.js`) usaba las APIs heredadas de la pantalla
de Archivos (`OCA.Files.fileActions` y `OCA.Sharing.ShareTabSections`),
y Nextcloud 33 ya no las trae: el botón simplemente nunca nacía, sin
error visible. El motor PHP nunca estuvo roto (la API respondía bien).

**Cómo es ahora:**

- El código fuente del botón vive en `copy_embed_link/src/files.js`:
  registra UNA acción «Copy embed link» con la API moderna oficial,
  `@nextcloud/files` versión 4 (`registerFileAction`). La v4 es la
  única que Nextcloud 33 acepta: las acciones registradas con la v3
  son ignoradas por la pantalla de Archivos.
- La acción funciona igual que antes: un solo archivo o carpeta,
  llama a la misma API de siempre (`POST /api/links`), si la carpeta
  es privada pregunta primero con el texto exacto «This folder is
  private. Share just this file?» (ahora con el diálogo oficial de
  `@nextcloud/dialogs`), copia el enlace y avisa con un toast.
- Ese fuente NO se sube tal cual: se **compila** con Vite (la
  herramienta estándar de las apps de Nextcloud, configurada con
  `@nextcloud/vite-config`). Comandos, desde `copy_embed_link/`:
  `npm install` (una vez) y `npm run build`. El resultado es
  `js/copy_embed_link-files.mjs` (~593 KB; incluye las librerías),
  que es el archivo que el Listener de PHP carga en Archivos, con el
  mismo nombre de siempre — el PHP no se tocó.
- Dos detalles del build que no hay que «arreglar»: la configuración
  lleva `emptyOutputDirectory: false` para que el build no borre el
  script del panel (`copy_embed_link-panel.js`, que sigue siendo
  JavaScript plano escrito a mano), e `inlineCSS: true` para que los
  estilos del diálogo viajen dentro del propio bundle, porque el
  script se carga sin llamada de estilos.
- El paquete `.tar.gz` lleva solo lo de ejecución (appinfo, img, js,
  lib, templates). Fuera quedan: `node_modules/`, `src/`,
  `package.json`, `package-lock.json`, `vite.config.mjs` y el mapa
  de fuentes (`.mjs.map`, se regenera en cada build).

**El hallazgo del menú «Embed links»:** la entrada no aparecía por un
error en nuestro `info.xml`, no del servidor. El servidor solo lee la
estructura `<navigations><navigation>…</navigation></navigations>`
(así lo hace su `NavigationManager` y así lo documenta la ficha de
metadatos de la tienda); nuestro archivo traía un `<navigation>`
suelto, que se ignora en silencio. Corregido en la v0.3.0. La página
del manual de desarrollo que muestra el `<navigation>` suelto está
desactualizada frente al código del servidor.
