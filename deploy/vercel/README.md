# Proxy de Vercel

La app (PHP + Apache) corre en Railway. Este proyecto de Vercel solo
publica el dominio `.vercel.app` y reenvía cada petición a Railway.

Desplegar: `cd deploy/vercel && vercel --prod`
Si cambia el dominio de Railway, actualizar `vercel.json`.
