# Prompt — Carril rápido (sin AgentSj)

Usa este prompt para **consultas** o **fixes** que **no** requieren flujo multi-agente.

## Cuando usar (sin AgentSj)

- Consulta sobre código, permisos, arquitectura → **Ask mode** + `consulta rapida`
- Fix en 1–3 archivos, sin permisos nuevos, migraciones ni rutas → **Agent mode** + `fix pequeno`
- Cambios medianos en un módulo ya existente (mismo patrón, sin rediseñar permisos) → Agent + alcance cerrado; **sin** `@agent-sj`

## Cuando SÍ usar AgentSj

- Feature o módulo nuevo
- Cambios en `config/access.php`, auth, migraciones, navegación nueva
- Trabajo que el usuario pida explícitamente orquestado

## Cómo escribir el prompt (copiar/pegar)

### Consulta — Ask mode

```text
consulta rapida — fuera de AgentSj.
Pregunta: [tu pregunta]
No modificar código. Contexto mínimo.
```

### Fix pequeño — Agent mode

```text
fix pequeno — fuera de AgentSj. No lanzar subagentes.
Archivo(s): [rutas exactas]
Objetivo: [qué corregir]
No tocar: permisos, rutas nuevas, migraciones, config/access.php, otros módulos.
Contexto mínimo: lee solo lo necesario; tests con --filter; sin navegador.
```

### Cambio en un módulo (sin orquestar)

```text
Implementa directo (NO AgentSj, NO FEAT, NO subagentes).
Módulo: [nombre]
Objetivo: [descripción]
Archivos esperados: [si los conoces]
Si hay duda de permisos o datos, pregunta y pausa.
```

## Palabras clave

| Carril | Palabra clave | AgentSj |
| --- | --- | --- |
| Consulta | `consulta rapida` | No |
| Fix | `fix pequeno` | No |
| Directo | `fuera de AgentSj` / `NO AgentSj` | No |
| Feature orquestada | `@agent-sj` o mensaje que empieza por `AgentSj` | Sí |

No uses `@agent-sj` ni empieces el mensaje con `AgentSj` si quieres carril rápido.

## Referencia

Flujo completo: [`docs/AGENT_WORKFLOW.md`](../../AGENT_WORKFLOW.md).  
Reglas de ahorro: [`.cursor/rules/contexto-minimo.mdc`](../../../.cursor/rules/contexto-minimo.mdc).  
Pruebas UI: [`.cursor/rules/manual-ui-verification.mdc`](../../../.cursor/rules/manual-ui-verification.mdc).
