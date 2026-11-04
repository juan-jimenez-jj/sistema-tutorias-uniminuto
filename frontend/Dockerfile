# syntax=docker/dockerfile:1.7
# Interfaz web: se compila con Vite y se sirve estática desde nginx,
# que además actúa como proxy inverso hacia la API (mismo origen → cookies SameSite=Strict).
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
ARG VITE_DEMO=0
ENV VITE_DEMO=$VITE_DEMO
RUN npm run build

FROM nginx:1.27-alpine
COPY --from=build /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
HEALTHCHECK --interval=15s --timeout=3s CMD wget -qO- http://127.0.0.1/healthz || exit 1
EXPOSE 80
