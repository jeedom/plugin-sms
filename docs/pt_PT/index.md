Configuração 
=============

O plug-in SMS permite que você interaja com o Jeedom através de
SMS, também permite que a Jeedom lhe envie um SMS em caso de alerta
(plugin de alarme, cenário ...)

> **IMPORTANTE**
>
> Para interagir com Jeedom, você deve ter configurado interações.

Configuração do plugin 
-----------------------

Depois de baixar o plugin, você só precisa ativá-lo e
porta de configuração. Depois de salvar, o daemon deve ser iniciado. O
o plugin já está configurado por padrão; então você não tem nada a ver com
mais No entanto, você pode modificar esta configuração. Aqui está o
detalhe (alguns parâmetros podem estar visíveis apenas no modo especialista) :

-   *Porta SMS* : a porta USB na qual sua chave GSM
        está conectado.

> **Dica**
>
> Se você não souber qual porta USB é usada, poderá simplesmente
> indicar "Auto". Atenção, o modo automático funciona apenas com as teclas
> Huawai E220.

> **IMPORTANTE**
>
> Observe que algumas teclas 3G estão por padrão no modo modem e não GSM.
> Usando o software do fabricante principal, você deve alterar o
> modo de chave no GSM (ou texto ou serial). 

-   **Código PIN** : Permite indicar o código PIN do cartão SIM e deixar em branco se você não tiver um. 
-   **Modo de texto** : Modo de compatibilidade estendida, a ser usado apenas se o envio e / ou recebimento de mensagens não funcionar.
-   **Cortar mensagens por pacote de caracteres** : Indica o número máximo de caracteres por texto.
-   **Gateway SMS / SMS (modificar em caso de erro : Número SMSC do CMS 330 não definido)** : Observe que, se o erro "Número do SMSC do CMS 330 não estiver definido", nesse caso, você deve indicar o número do gateway SMS da operadora de telefonia. 
-   **Intensidade do sinal** : Força de recepção do sinal da sua chave GSM.
-   **Rede** : Rede telefônica da sua chave GSM (pode ser "Nenhuma" se a Jeedom não puder recuperá-la). 
-   **tomada interna (modificação perigosa)** : permite modificar a porta de comunicação interna do daemon.
-   **Ciclo (s)** : ciclo de varredura de daemon para enviar e receber SMS. Um número muito baixo pode causar instabilidade

Configuração do equipamento 
-----------------------------

A configuração do equipamento SMS pode ser acessada no menu
plugin e comunicação

Aqui você encontra toda a configuração do seu equipamento :

-   **Nome de l'équipement SMS** : nome do seu equipamento SMS,

-   **Ativar** : torna seu equipamento ativo,

-   **Visivél** : torna seu equipamento visível no painel,

-   **Objeto pai** : indica o objeto pai ao qual
    pertence a equipamento.

Abaixo você encontra a lista de pedidos :

-   **Nome** : o nome exibido no painel,

-   **Usuário** : usuário correspondente no Jeedom (permite
    restringir certas interações a certos usuários),

-   **Número** : número de telefone para o qual enviar mensagens. Você
    pode colocar vários números separando-os com; (ex
    : 0612345678; 0698765432).

    > **IMPORTANTE**
    >
    > Somente números de telefone declarados no equipamento podem
    > usar interações porque somente elas serão permitidas.

-   **Display** : permite exibir os dados no painel,

-   **Configuração avancée** (pequenas rodas dentadas) : permet
    exibir a configuração avançada do comando (método
    história, widget ...),

-   **Teste** : permite testar o comando,

-   **Remover** (sinal -) : permite excluir o comando.

Usando o plugin 
---------------------

Isso é bastante padrão em sua operação, na página Geral
→ Plugin e depois selecionando o plugin SMS :

-   A porta (caminho) para o dispositivo que serve como modem (por exemplo
    exemplo, pode ser / dev / ttyUSB0, para vê-lo apenas iniciar
    “dmesg” puis de brancher le modem)

-   O código PIN do cartão SIM

    Então você precisa adicionar um novo equipamento e configurá-lo :

-   O nome dele,

-   Se está ativo ou não,

    Em seguida, adicione os comandos que serão compostos por um nome e
    um número, apenas os números listados na lista de comandos
    pode receber uma resposta da Jeedom (isso garante,
    evitando colocar uma senha no início de cada SMS
    enviado para Jeedom). Você também pode indicar qual usuário está
    vinculado a esse número (para gerenciamento de direitos no nível
    interações).

Para se comunicar com Jeedom, basta enviar a ele um
mensagem de um número autorizado, todas as interações provenientes do
sistema de interação.

Pequeno exemplo de interação : Pergunta : “Qual é a temperatura de
o quarto ?” Réponse : “16.3 C”

Lista de chaves compatíveis 
---------------------------

-   HUAWEI E220
-   Alcatel one touch X220L
-   HSDPA 7.2MBPS 3G sem fio
-   HUAWEI E3372


Faq 
===

> **Não recebo nada com uma chave huwaei e160.**
>
>Você precisa instalar o minicom (sudo apt-get install -y minicom), iniciar
>este e conecte-se ao modem e faça :
>
>`` `{.bash}
>AT ^ CURC = 0
>AT ^ U2DIAG = 0
>`` ''
>
>E no plugin faça :
>
>-   Escolha a primeira porta USB e não a segunda
>
>-   Velocidade : 9600
>
>-   Modo de texto desativado

> **Não consigo ver a porta USB na minha chave**
>
>Verifique se você não possui o brltty para instalar (`sudo apt-get remove brltty` para removê-lo)

> **Depois de algumas horas / dias, não recebo mais um SMS e não posso mais enviar um, um lembrete do demônio corrige**
>
>Verifique seu cabo USB (um cabo USB ruim geralmente resulta em
>tipo de preocupações, também não deve demorar muito),
>Além disso, verifique sua fonte de alimentação, um hub USB é fortemente
>conseillé

> **Eu tenho um erro CME XX**
>
>Você pode encontrar [aqui](:http://www.micromedia-int.com/fr/gsm-2/669-cme-error-gsm-equipment-related-errors) descrição dos diferentes erros CME

> **Configuração de la clef Alcatel one touch X220L**
>
>Quando inserimos a chave, temos esse :
>
>`` `{.bash}
>root @ jeedom:# lsusb
>Dispositivo 002 do barramento 002: ID 1bbb:f000 Telefones Celulares
>`` ''
>
>Cuidado se você não tem 1bbb:f000 é importante não fazer o
>continuação desta documentação
>
>adicione as seguintes linhas no final do arquivo
>/etc/usb\_modeswitch.conf :
>
>`` `{.bash}
>########################################################
># Alcatel X220L
>DefaultVendor = 0x1bbb
>DefaultProduct = 0xf000
>MessageContent = "55534243123456788000000080000606f504025270000000000000000000000000"
>########################################################
>`` ''
>
>Em seguida, execute o seguinte comando para testar :
>
>`` `{.bash}
>/ usr / sbin / usb_modeswitch -c
>/etc/usb_modeswitch.conf
>`` ''
>
>Nós entendemos isso :
>
>`` `{.bash}
>root @ jeedom:~ # lsusb
>Dispositivo 002 do barramento 002: ID 1bbb:0017 Telefones celulares
>`` ''
>
>e os links em / dev são adicionados :
>
>`` `{.bash}
>root @ jeedom:~ # ls / dev / ttyUSB*
>/ dev / ttyUSB0 / dev / ttyUSB1 / dev / ttyUSB2 / dev / ttyUSB3 / dev / ttyUSB4
>`` ''
>
>Agora você precisa automatizar o lançamento do comando anterior
>via udev :
>
>`` `{.bash}
>root @ jeedom:# vi /etc/udev/rules.d99-usb_modeswitch.rules
>SUBSISTEMA == "usb", ATTRS {idVendor} == "1bbb", ATTRS {idProduct} == "f000", EXECUTAR + = "/ usr / sbin / usb_modeswitch -c /etc/usb_modeswitch.conf"
>`` ''
>
>Sob jeedom no plug-in SMS, você deve (no meu caso) usar o "port
>SMS "próximo : / dev / ttyUSB3. Basicamente, você deve tentar cada porta para
>encontre o caminho certo…

> **O daemon SMS é iniciado, mas você não recebe nenhum SMS**
>
>Uma das causas prováveis é a configuração de rede incorreta. Em
>"Geral "->" Configuração "->" Administração" ->
>"Configuração de rede ", verifique o conteúdo do campo" URL ou endereço IP".
>
>O último não deve ser localhost ou 127.0.0.1, mas o endereço IP de
>seu nome Jeedom ou DNS.

> **No modo de depuração, tenho o erro "timeout" que aparece**
>
>Este erro ocorre quando a chave não responde dentro de 10 segundos
>que seguem uma solicitação. As causas conhecidas podem ser :
>
>-   incompatibilidade da chave GSM,
>
>-   problema com a versão do firmware do stick.

> **Ao iniciar no modo de depuração, tenho : "soquete já em uso"**
>
>Isso significa que o demônio foi iniciado, mas que Jeedom não chega
>para parar. Você pode reiniciar o sistema inteiro ou
>SSH do "killall -9 refxcmd.py".

> **O demônio se recusa a começar**
>
>Tente iniciá-lo no modo de depuração para ver o erro.

> **Eu tenho várias portas USB para minha chave GSM, enquanto eu tenho apenas uma**
>
>Isso é normal, por um motivo desconhecido, as chaves GSM criam 2 (e
>ainda mais) portas USB no nível do sistema. Basta escolher um,
>quem.

> **Jeedom não envia mais nem recebe SMS**
>
>Isso geralmente acontece se a chave GSM não puder mais se conectar
>para a rede. Tente movê-lo e veja se ele volta ao fim
>alguns minutos.

>**Tenho preocupações de recepção que funcionam por algumas horas, então nada**
>
>Coloque o cartão SIM em um telefone celular e esvazie todos os sms (enviados e recebidos) do cartão.
