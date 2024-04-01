/**
 * Please keep in mind that since Moodle 2.0 all of the CSS for the whole of
 * combined into one file, stripped of white spaces, then delivered.
 *
 * Please visit the CSS coding style page:
 * https://docs.moodle.org/dev/CSS_coding_style
 */
 
sass --watch style/scss/moodle.scss:style/moodle.css

Pasos para Instalar un Certificado SSL en un Subdominio en Amazon AWS

1. Generar un CSR (Certificate Signing Request):
   - Acceder a la instancia de AWS donde está alojado el subdominio.
   - Utilizar OpenSSL u otra herramienta para generar un CSR.
   - Asegurarse de que el CN (Common Name) sea el subdominio.

2. Adquirir un certificado SSL:
- Envía el archivo CSR generado anteriormente a tu proveedor de cert
   - AWS Certificate Manager ¿?
   - Lets Encrypt. si lo tiene

3. Configurar el certificado en AWS Certificate Manager:
   - Si utilizamos AWS Certificate Manager, siguer instrucciones para solicitar y aprobar el certificado.

4. Instala el certificado en la instancia de AWS:
   - Configurar servidor (Apache, Nginx, etc.) para cargar el certificado y la clave privada.

5. Configurar servidor:
   - Asegúrarse de que el servidor escuche en el puerto 443 (HTTPS).
   - Configurar el servidor para servir contenido del subdominio utilizando el certificado SSL.
