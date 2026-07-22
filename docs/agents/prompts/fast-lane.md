# Prompt — Carril rapido

Usa este prompt para **consultas** o **fixes pequenos** que **no** requieren flujo multi-agente completo.

## Cuando usar

- Consulta sobre codigo, permisos, arquitectura → **Ask mode**
- Fix en 1–3 archivos, sin permisos nuevos, migraciones ni rutas → **Agent mode**

## Cuando NO usar (flujo completo)

- Feature nueva o modulo nuevo
- Cambios en `config/access.php`, auth, migraciones, navegacion
- Trabajo paralelo de varios agentes

## Palabras clave

`consulta rapida` | `fix pequeno`

## Instruccion tipo — consulta

```text
Consulta rapida — fuera del flujo multi-agente.
Pregunta: [tu pregunta]
Leer AGENTS.md y docs/modules/ si aplica. No modificar codigo.
```

## Instruccion tipo — fix pequeno

```text
Fix pequeno — fuera del flujo multi-agente.
Archivo(s): [ruta exacta]
Objetivo: [que corregir]
No tocar: permisos, rutas, migraciones, config/access.php, otros modulos.
```

## Referencia

Flujo completo: [`docs/AGENT_WORKFLOW.md`](../../AGENT_WORKFLOW.md) y [`start-feature.md`](start-feature.md).
