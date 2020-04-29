Configuración 
=============

El complemento de SMS le permite interactuar con Jeedom a través de
SMS, también le permite a Jeedom enviarle un SMS en caso de alerta
(plugin de alarma, escenario ...)

> **Important**
>
> Para interactuar con Jeedom debe haber configurado interacciones.

Configuración del plugin 
-----------------------

Después de descargar el complemento, solo necesita activarlo y
configurar puerto. Después de guardar el demonio debería lanzarse. El
el complemento ya está configurado de manera predeterminada; así que no tienes nada que ver con
Más. Sin embargo, puede modificar esta configuración. He aquí el
detalle (algunos parámetros solo pueden ser visibles en modo experto) :

-   *Puerto de SMS* : el puerto USB en el que su clave GSM
        está conectado.

> **Tip**
>
> Si no sabe qué puerto USB se utiliza, puede simplemente
> indicar "Auto". Atención, el modo automático solo funciona con las teclas
> Huawai E220.

> **Important**
>
> Tenga en cuenta que algunas teclas 3G están por defecto en modo módem y no GSM.
> Usando el software de su fabricante clave, debe cambiar el
> modo clave en GSM (o texto, o serie). 

-   **Código PIN** : Le permite indicar el código PIN de la tarjeta SIM, dejar en blanco si no tiene una. 
-   **Modo de texto** : Modo de compatibilidad extendida, para ser usado solo si enviar y / o recibir mensajes no funciona.
-   **Cortar mensajes por paquete de caracteres** : Indica el número máximo de caracteres por texto..
-   **SMS / SMS Gateway (modificar en caso de error : CMS 330 número SMSC no establecido)** : Tenga en cuenta que si tiene el error "Número de SMSC CMS 330 no establecido", en este caso debe indicar el número de puerta de enlace de SMS de su operador telefónico. 
-   **Fuerza de la señal** : Intensidad de recepción de señal de su clave GSM.
-   **Red** : Red telefónica de su clave GSM (puede ser "Ninguna" si Jeedom no puede recuperarla). 
-   **toma interna (modificación peligrosa)** : permite modificar el puerto de comunicación interna del demonio.
-   **Ciclo (s)** : ciclo de escaneo de demonios para enviar y recibir SMS. Un número demasiado bajo puede causar inestabilidad.

Configuración del equipo 
-----------------------------

Se puede acceder a la configuración del equipo de SMS desde el menú
plugin luego comunicación

Aquí encontrarás toda la configuración de tu equipo :

-   **Nombre del equipo SMS** : nombre de su equipo de SMS,

-   **Activer** : activa su equipo,

-   **Visible** : hace que su equipo sea visible en el tablero,

-   **Objeto padre** : indica el objeto padre al que
    pertenece equipo.

A continuación encontrará la lista de pedidos. :

-   **Nom** : el nombre que se muestra en el tablero,

-   **Utilisateur** : usuario correspondiente en Jeedom (permite
    restringir ciertas interacciones a ciertos usuarios),

-   **Número** : número de teléfono al que enviar mensajes. Vosotras
    puede poner varios números separándolos con; (ex
    : 0612345678; 0698765432).

    > **Important**
    >
    > Solo los números de teléfono declarados en el equipo pueden
    > usar interacciones porque solo se permitirán.

-   **Afficher** : permite mostrar los datos en el tablero,

-   **Configuración avanzada** (ruedas con muescas pequeñas) : permite
    muestra la configuración avanzada del comando (método
    historia, widget ...),

-   **Tester** : Se usa para probar el comando,

-   **Supprimer** (signo -) : permite eliminar el comando.

Usando el complemento 
---------------------

Esto es bastante estándar en su funcionamiento, en la página General
→ Complemento luego seleccionando el complemento de SMS :

-   El puerto (ruta) al dispositivo que sirve como módem (por ejemplo
    ejemplo puede ser / dev / ttyUSB0, para verlo simplemente inicie
    “dmesg” puis de brancher le modem)

-   El código pin de la tarjeta sim

    Por lo tanto, debe agregar nuevo equipo y luego configurarlo :

-   El nombre,

-   Si está activo o no,

    Luego agregue los comandos que estarán compuestos de un nombre y
    un número, solo los números listados en la lista de comandos
    puede recibir una respuesta de Jeedom (esto asegura,
    evitando poner una contraseña al comienzo de cada SMS
    enviado a Jeedom). También puede indicar qué usuario es
    vinculado a este número (para la gestión de derechos a nivel
    interacciones).

Para comunicarse con Jeedom, será suficiente enviarle un
mensaje de un número autorizado, todas las interacciones provienen del
sistema de interaccion.

Pequeño ejemplo de interacción : Pregunta : “¿Cuál es la temperatura de
Habitación ?” Respuesta : “16.3 C”

Lista de claves compatibles 
---------------------------

-   HUAWEI E220
-   Alcatel one touch X220L
-   HSDPA 7.2MBPS 3G inalámbrico
-   HUAWEI E3372


Preguntas frecuentes 
===

> **No consigo nada con una llave huwaei e160.**
>
>Debe instalar minicom (sudo apt-get install -y minicom), inicie
>este y conectarse al módem y luego hacer :
>
>`` `{.bash}
>AT ^ CURC = 0
>AT ^ u2diag = 0
>`` ''
>
>Y en el complemento hacer :
>
>-   Elija el primer puerto USB y no el segundo
>
>-   Velocidad : 9600
>
>-   Modo de texto desactivado

> **No puedo ver el puerto USB en mi llave**
>
>Asegúrese de que no tiene que instalar brltty (`sudo apt-get remove brltty` para eliminarlo)

> **Después de unas pocas horas / días ya no recibo un SMS y ya no puedo enviar uno, un recordatorio del demonio corrige**
>
>Verifique su cable USB (un cable USB defectuoso a menudo resulta en
>tipo de preocupaciones, tampoco debería ser demasiado largo),
>También verifique su fuente de alimentación, un concentrador USB es fuertemente
>conseillé

> **Tengo un error de CME XX**
>
>Puedes encontrar [aquí](:http://www.micromedia-int.com/fr/gsm-2/669-cme-error-gsm-equipment-related-errors) descripción de los diferentes errores de CME

> **Configuración de la tecla Alcatel one touch X220L**
>
>Cuando insertamos la clave, tenemos esto :
>
>`` `{.bash}
>root @ jeedom:# lsusb
>Bus 002 Dispositivo 003: ID 1bbb:Teléfonos móviles f000 T&A
>`` ''
>
>Ten cuidado si no tienes 1bbb:f000 es importante no hacer el
>continuación de esta documentación
>
>agregue las siguientes líneas al final del archivo
>/etc/usb\_modeswitch.conf :
>
>`` `{.bash}
>########################################################
># Alcatel X220L
>DefaultVendor = 0x1bbb
>DefaultProduct = 0xf000
>MessageContent = "55534243123456788000000080000606f50402527000000000000000000000"
>########################################################
>`` ''
>
>Luego ejecute el siguiente comando para probar :
>
>`` `{.bash}
>/ usr / sbin / usb_modeswitch -c
>/etc/usb_modeswitch.conf
>`` ''
>
>Entendemos esto :
>
>`` `{.bash}
>root @ jeedom:~ # lsusb
>Bus 002 Dispositivo 003: ID 1bbb:0017 T & A Teléfonos móviles
>`` ''
>
>y se agregan los enlaces bajo / dev :
>
>`` `{.bash}
>root @ jeedom:~ # ls / dev / ttyUSB*
>/ dev / ttyUSB0 / dev / ttyUSB1 / dev / ttyUSB2 / dev / ttyUSB3 / dev / ttyUSB4
>`` ''
>
>Ahora tienes que automatizar el lanzamiento del comando anterior
>vía udev :
>
>`` `{.bash}
>root @ jeedom:# vi /etc/udev/rules.d99-usb_modeswitch.rules
>SUBSISTEMA == "usb", ATTRS {idVendor} == "1bbb", ATTRS {idProduct} == "f000", RUN + = "/ usr / sbin / usb_modeswitch -c /etc/usb_modeswitch.conf"
>`` ''
>
>Bajo jeedom en el complemento de SMS, debe (en mi caso) usar el "puerto
>SMS "siguiente : / dev / ttyUSB3. Básicamente tienes que probar cada puerto para
>encuentra el correcto ...

> **El daemon de SMS se inicia, pero no recibe ningún SMS**
>
>Una de las causas probables es la configuración de red incorrecta. Dentro
>"General "->" Configuración "->" Administración" ->
>"Configuración de red ", verifique el contenido del" campo URL o dirección IP".
>
>Este último no debe ser localhost o 127.0.0.1 pero la dirección IP de
>su nombre Jeedom o DNS.

> **En modo de depuración tengo el error "timeout" que aparece**
>
>Este error ocurre cuando la clave no responde en 10 segundos
>quien sigue una solicitud. Las causas conocidas pueden ser :
>
>-   incompatibilidad de la clave GSM,
>
>-   problema con la versión de firmware del stick.

> **Al comenzar en modo de depuración tengo : "zócalo ya en uso"**
>
>Significa que el demonio ha comenzado pero que Jeedom no llega.
>para detenerlo. Puede reiniciar todo el sistema o por
>SSH do "killall -9 refxcmd.py".

> **El demonio se niega a comenzar.**
>
>Intenta iniciarlo en modo de depuración para ver el error.

> **Tengo varios puertos USB para mi llave GSM, mientras que solo tengo uno**
>
>Esto es normal, por una razón desconocida las claves GSM crean 2 (y
>aún más) puertos USB a nivel de sistema. Solo elige uno,
>quien sea.

> **Jeedom ya no envía ni recibe SMS**
>
>Esto suele suceder si la clave GSM ya no puede conectarse
>a la red. Intenta moverlo y ver si vuelve al final
>unos minutos.

>**Tengo problemas de recepción que funcionan durante unas horas y luego nada**
>
>Coloque la tarjeta SIM en un teléfono móvil y vacíe todos los sms (enviados y recibidos) de la tarjeta.
