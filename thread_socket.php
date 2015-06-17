<?php
ini_set('memory_limit','2048M');

//多线程socket服务器
error_reporting(E_ALL^E_NOTICE);
$socket_handle=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($socket_handle,'0.0.0.0',12345);
socket_listen($socket_handle,10);
socket_set_nonblock($socket_handle);
$client_id=0;
$thread_pool=array();
echo "start socket server: main thread #".Thread::getCurrentThreadId()." \n";
while(true){
    if($client=socket_accept($socket_handle)){
        $client_id++;
        $thread_pool[$client_id]=new ClientThread($client);
        $thread_pool[$client_id]->start();
        echo "start thread: #".$thread_pool[$client_id]->getThreadId()."  \n";
    }else{
        foreach($thread_pool as $key=>$t){
            if(!$t->isRunning()){
                echo "stop thread: #".$t->getThreadId()."  \n";
                unset($thread_pool[$key]);
            }
        }
        usleep(200);
    }
}
socket_close($socket_handle);
echo "socket server stop\n";






class ClientThread extends Thread {

    protected $client=null;

    public function __construct($client){
        $this->client = $client;
    }
    public function run(){
        socket_write($this->client,"server:hellow !\r\n");
        while(true){
            socket_set_nonblock($this->client);
            $msg=@socket_read($this->client,1024*1000);

            if($msg===false){
                if(false===@socket_write($this->client,"\x20\x08")){
                    socket_shutdown($this->client);
                    return ;
                }else{
                    usleep(1000);
                    continue;
                }

            }

            $msg=rtrim($msg,"\n\r");
            if($msg){
                switch($msg){
                    case 'logout':
                        socket_write($this->client,"server:logout success !\r\n");
                        socket_shutdown($this->client);
                        return;
                        break;
                    default:
                        socket_write($this->client,"server: received msg [".$msg."] !\r\n");
                        break;
                }
            }
        }
    }

}
?>


