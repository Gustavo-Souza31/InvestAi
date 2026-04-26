"""
Configuração da chave Gemini API.
Lê de variável de ambiente GEMINI_API_KEY ou do arquivo .env na raiz do projeto.
"""
import os

def get_gemini_key() -> str | None:
    # 1) Variável de ambiente direta
    key = os.environ.get("GEMINI_API_KEY")
    if key:
        return key.strip()

    # 2) Arquivo .env na raiz do projeto (pasta pai de /ia/)
    env_path = os.path.join(os.path.dirname(__file__), "..", ".env")
    env_path = os.path.normpath(env_path)
    if os.path.isfile(env_path):
        with open(env_path, "r") as f:
            for line in f:
                line = line.strip()
                if line.startswith("GEMINI_API_KEY="):
                    return line.split("=", 1)[1].strip().strip('"').strip("'")

    return None
