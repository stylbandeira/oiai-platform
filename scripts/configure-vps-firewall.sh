#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Execute como root (sudo)." >&2
    exit 1
fi

command -v ufw >/dev/null || {
    echo "UFW não está instalado. Instale-o antes de continuar." >&2
    exit 1
}

SSH_PORT="${VPS_SSH_PORT:-22}"

if ! [[ "$SSH_PORT" =~ ^[0-9]+$ ]] || (( SSH_PORT < 1 || SSH_PORT > 65535 )); then
    echo "VPS_SSH_PORT inválida: $SSH_PORT" >&2
    exit 1
fi

echo "Liberando SSH na porta ${SSH_PORT} antes de ativar o firewall..."
ufw allow "${SSH_PORT}/tcp" comment 'SSH'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw default deny incoming
ufw default allow outgoing

echo "Regras que serão aplicadas:"
ufw show added
read -r -p "Ativar o UFW agora? [y/N] " answer
if [[ "$answer" =~ ^[Yy]$ ]]; then
    ufw --force enable
    ufw status verbose
else
    echo "Nenhuma ativação foi executada. Revise as regras e rode novamente."
fi
