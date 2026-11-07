// Endereço da API. Este é o valor padrão (usado fora do Docker).
// Dentro do Docker, o Nginx do frontend responde a /config.js com o valor da
// variável de ambiente API_URL (veja default.conf.template e o compose.yaml).
export const API_URL = '/api';