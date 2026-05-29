# Design System: CRM-AC

## 1. Visual Theme & Atmosphere
Interfaz ERP B2B de precisión — claridad clínica con calidez. La atmósfera es como un estudio de arquitectura bien iluminado: espacioso, organizado, con propósito. Cada decisión visual prioriza la legibilidad y la facilidad cognitiva para usuarios de negocio no técnicos.

- **Densidad:** 5 — Balanceada. Las tarjetas respiran, las tablas son compactas.
- **Varianza:** 4 — Asimetría estructurada. Disciplina de cuadrícula sobre caos.
- **Movimiento:** 3 — Contenido. Transiciones de estado 150–200ms, sin animaciones de teatro.

---

## 2. Color Palette & Roles

### Superficies Base
- **Canvas Frost** (#F8FAFC) — Fondo de página, equivalente slate-50
- **Pure Surface** (#FFFFFF) — Tarjetas, modales, sidebar
- **Whisper Border** (rgba(148,163,184,0.20)) — Bordes de tarjeta, divisores de tabla

### Jerarquía de Texto
- **Slate Ink** (#0F172A) — Títulos primarios, valores de datos en negrita
- **Iron Text** (#334155) — Cuerpo, descripciones, slate-700
- **Muted Steel** (#64748B) — Etiquetas, metadatos, captions
- **Quiet Ghost** (#94A3B8) — Placeholder, estados deshabilitados

### Acentos de Módulo (máximo uno activo por vista)
- **Inventory Emerald** (#059669) — Inventario: CTAs, nav activo, badges, focus rings
- **Finance Amber** (#D97706) — Finanzas: CTAs, nav activo, gráficas
- **Intelligence Violet** (#6D28D9) — IA/Estadísticas: CTAs, nav activo, reportes

### Colores Semánticos
- **Alert Crimson** (#DC2626) — Errores, alertas de stock, acciones destructivas
- **Success Leaf** (#16A34A) — Confirmación, tendencias positivas
- **Caution Sand** (#B45309) — Advertencias, estados pendientes

---

## 3. Typography Rules

- **Primary Face:** Outfit (Google Fonts) — pesos 400, 500, 600, 700. Tracking ajustado para títulos, relajado para cuerpo.
- **Monospace:** JetBrains Mono (Google Fonts) — Obligatorio para: todos los valores KPI numéricos, montos de divisas, códigos SKU, fechas en tablas, etiquetas de ejes en gráficas.
- **Escala:** Display 1.5rem/600 → Sección 1.125rem/600 → Cuerpo 0.875rem/400 → Etiqueta 0.6875rem/500 → Caption 0.6875rem/400
- **Prohibidos:** Inter (demasiado genérico). Georgia, Times New Roman (serif prohibido en dashboards).
- **Line height:** 1.6 para cuerpo, 1.2 para títulos, 1.4 para celdas de tabla
- **Ancho máximo de línea:** 65ch para contenido de prosa (secciones de reporte IA)

---

## 4. Component Stylings

**Tarjetas KPI:**
Superficie plana (#FFFFFF), radius 1.25rem, borde 1px Whisper Border, sin sombra. Borde izquierdo de acento (4px sólido, color del módulo). Etiqueta en Outfit 11px/600 mayúsculas con tracking. Valor en JetBrains Mono 1.5rem/600 Slate Ink con `tabular-nums`.

**Gráficas:**
Contenedor blanco, radius 1.25rem, borde 1px Whisper Border, sin sombra. Altura fija del canvas: 256px. Leyenda como fila explícita sobre el canvas (no dentro del canvas). Etiquetas de ejes en JetBrains Mono 11px, Muted Steel. Líneas de cuadrícula: 1px rgba(148,163,184,0.12).

**Tablas de Datos:**
Header: fondo Canvas Frost, Outfit 11px/600 mayúsculas con tracking, Muted Steel. Filas de cuerpo: 14px, alternancia sutil blanco / rgba(248,250,252,0.5). Hover: rgba(248,250,252,0.9). Padding de celda: 14px horizontal, 12px vertical. Sin borde exterior en el elemento `<table>`.

**Sidebar:**
256px fijo. Superficie blanca, borde derecho 1px Whisper Border. Items de nav: Outfit 14px/500, 40px altura, padding horizontal 12px. Inactivo: Iron Text + bg transparente. Hover: Canvas Frost bg. Activo: texto acento del módulo + bg acento-50 + borde izquierdo 3px sólido color acento.

**Botones:**
Primario: bg color acento del módulo, texto blanco, radius 0.75rem, altura 2.5rem, Outfit 14px/600. Sin glow exterior. Estado activo: -1px translateY. Focus: ring 3px color acento offset 2px.
Secundario/Ghost: bg blanco, borde 1px Whisper Border, Iron Text.

**Inputs de Formulario:**
Etiqueta encima, Outfit 14px/500 Iron Text, gap 4px. Input: bg blanco, borde 1px slate-200, radius 0.75rem, 14px font-size, 40px altura. Focus: borde 2px acento + ring 4px al 20% opacidad.

**Estados de Carga:**
Shimmer esquelético que coincide con dimensiones exactas. Sin spinners circulares genéricos.

---

## 5. Layout Principles

- **Grid:** CSS Grid para layouts multi-columna. KPIs: `grid-cols-2 lg:grid-cols-4`. Gráficas: `grid-cols-1 lg:grid-cols-2`.
- **Padding de página:** 2rem (32px) en todos los lados.
- **Gap entre secciones:** 1.5rem entre secciones mayores, 1rem dentro de secciones.
- **Sin elementos superpuestos:** Cada componente ocupa su zona espacial limpia.
- **Tablas:** Siempre dentro de contenedor blanco con wrapper `overflow-x-auto`.

---

## 6. Motion & Interaction

- **Duración:** 150ms para micro-interacciones (hover, focus), 200ms para transiciones de panel.
- **Easing:** `ease-out` para entradas, `ease-in` para salidas.
- **Propiedades animadas:** Solo `color`, `background-color`, `border-color`, `transform`.
- **Sin loops de animación** en componentes de datos.

---

## 7. Anti-Patterns (Prohibidos)

- **No emojis** en ningún lugar de la interfaz
- **No fuente Inter** — usar Outfit como primaria
- **No negro puro** (#000000) — mínimo Slate Ink (#0F172A)
- **No sombras neon** ni outer glow en botones
- **No colores de acento sobresaturados** (saturación máx. 75%)
- **No texto degradado** en títulos grandes
- **No spinners circulares** — usar shimmer esquelético
- **No copywriting de IA** ("Seamless", "Potente", "Revolucionario", "Next-Gen")
- **No datos fabricados** ni métricas inventadas
- **No emojis en botones** de asesor IA ni en navegación
- **No sombras en tablas** de datos — usar borde
