FROM node:20-alpine

WORKDIR /app

# Instalar dependencias de Node
COPY package*.json ./
RUN npm install

# Copiar el resto del proyecto
COPY . .

EXPOSE 5173

# Ejecutar el servidor de desarrollo de Vite
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
