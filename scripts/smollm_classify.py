#!/usr/bin/env python3
"""
Clasificador de tickets usando Qwen2.5 vía el SDK oficial de ollama.

Lee un JSON de stdin con el formato:
    {"message": "...", "system": "...", "model": "qwen2.5:0.5b"}

Escribe únicamente la respuesta del modelo en stdout.

Instalación:
    pip install ollama

Uso desde PHP (proc_open) o directamente:
    echo '{"message": "Hola", "system": "Eres un clasificador"}' | python3 scripts/smollm_classify.py
"""

import sys
import json

try:
    import ollama
except ImportError:
    print("ERROR: paquete 'ollama' no instalado. Ejecutá: pip install ollama", file=sys.stderr)
    sys.exit(1)


def main() -> None:
    raw = sys.stdin.read().strip()

    if not raw:
        print("ERROR: no se recibió input por stdin.", file=sys.stderr)
        sys.exit(1)

    try:
        data = json.loads(raw)
    except json.JSONDecodeError as e:
        print(f"ERROR: JSON inválido: {e}", file=sys.stderr)
        sys.exit(1)

    message      = data.get("message", "")
    system_prompt = data.get("system", "")
    model        = data.get("model", "qwen2.5:0.5b")

    if not message:
        print("ERROR: el campo 'message' está vacío.", file=sys.stderr)
        sys.exit(1)

    messages = []
    if system_prompt:
        messages.append({"role": "system", "content": system_prompt})
    messages.append({"role": "user", "content": message})

    try:
        response = ollama.chat(model=model, messages=messages, stream=False)
        content  = response["message"]["content"]
        print(content, end="")
    except ollama.ResponseError as e:
        print(f"ERROR: Ollama ResponseError: {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()
