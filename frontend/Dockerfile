# Frontend: HTML, CSS e JavaScript puros, entregues pelo Nginx.
# Não há "npm install" nem build, porque o projeto não usa bundler nem dependências.
# Por isso o build da imagem é reproduzível: copia os arquivos e pronto.
FROM nginx:alpine

# Endereço da API que o navegador vai usar (padrão: /api, via proxy).
# Para mudar: defina API_URL no compose.yaml (ou com "docker run -e API_URL=...").
ENV API_URL=/api

# A imagem do Nginx processa os arquivos de /etc/nginx/templates e troca ${API_URL}
COPY default.conf.template /etc/nginx/templates/default.conf.template

# O site em si
COPY . /usr/share/nginx/html

# O template e os arquivos de Docker não precisam ficar públicos
RUN rm -f /usr/share/nginx/html/default.conf.template /usr/share/nginx/html/Dockerfile /usr/share/nginx/html/.dockerignore

EXPOSE 80