<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link href="../css/bootstrap.css" rel="stylesheet" type="text/css">
        <link href="../css/jumbotron.css" rel="stylesheet" type="text/css">
        <title>Usuários</title>
    </head>
    
    <body>
        <?php
            session_start();
            //$caminho = $_SERVER['DOCUMENT_ROOT']."/teste_locaweb/trunk/";
            $caminho =  dirname(dirname( __FILE__ ));
            $caminho = $caminho.'/';
            require_once $caminho."Tweet.php";
            require_once $caminho."Usuario.php";
            require_once $caminho."ResultadoTweet.php";
            
            $listaTweet = unserialize($_SESSION['tweets']); // Pegando a lista de tweets salva na session
            
            //Vetores auxiliares
            $listaTweetMenc = array();
            $idUsers = array();
            $mostMentionsLocaweb = array();            
            $listaUsuarios = array();            
            $listagemTweets = array();
            $arr = array();
            
            // Pegando apenas os usuários que mencionaram a Locaweb
            foreach ($listaTweet as $lisTwe){
                if ($lisTwe->getId_str_mentions() == 42){
                    array_push($listaTweetMenc, $lisTwe);
                    if (!in_array($lisTwe->getId_str_user(), $idUsers)){
                        array_push($idUsers, $lisTwe->getId_str_user());
                    }
                }
            }
            
            for ($i=0; $i<count($idUsers);$i=$i+1){
                $id = $idUsers[$i];
                
                // Agrupando os tweets dos usuários
                $ret = array_filter($listaTweetMenc, function ($lista) use ($id){
                                                    return $lista->getId_str_user() == $id;
                                                 }
                        );
                
                $totalSeguidores = 0;
                $totalRetweets = 0;
                $totalLikes = 0;
                $totalMentions = 0;
                
                // A função array_filter irá retornar os tweets agrupados pelos usuários, o que pode ser um vetor. 
                // Caso seja, e de acordo com a regra de relevância, estes serão ordenados de acordo com a avaliação.
                usort(
                    $ret,
                    function($a,$b) {
                        if($a->getAvaliacao() == $b->getAvaliacao()) return 0;
                        return (($a->getAvaliacao() > $b->getAvaliacao()) ? -1 : 1 );
                    }
                );
                
                
                foreach ($ret as $rt){
                   $totalSeguidores = $rt->getFollowersCount();
                   $totalRetweets += $rt->getRetweetCount();
                   $totalLikes += $rt->getFavoritesCount();
                   $totalMentions = $totalMentions+ 1;
                }

                $usuario = new Usuario();
                
                $usuario->setIdUser($id);
                $usuario->setTotalFollowers($totalSeguidores);
                $usuario->setTotalLikes($totalLikes);
                $usuario->setTotalRetweets($totalRetweets);
                $usuario->setTotalMentions($totalMentions);
                $usuario->setIdPosi($i);
                
                $usuario->avaliarTweetUsuario();
                
                array_push($listaUsuarios, $usuario);
                array_push($mostMentionsLocaweb, $ret);
            }

            usort(
                $listaUsuarios,
                function($a,$b) {
                    if($a->getAvaliacao() == $b->getAvaliacao()) return 0;
                    return (($a->getAvaliacao() > $b->getAvaliacao()) ? -1 : 1 );
                }
            );           
            
            for ($i=0; $i<count($listaUsuarios);$i=$i+1){
                
                $subarray = array();
                
                $posi = $listaUsuarios[$i]->getIdPosi();
                
                foreach($mostMentionsLocaweb[$posi] as $mm) {
                    $resultado = new ResultadoTweet();
                    $resultado->setCreated_at($mm->getCreatAt());
                    $resultado->setFavourites_count($mm->getFavoritesCount());
                    $resultado->setFollowers_count($mm->getFollowersCount());
                    $resultado->setRetweet_count($mm->getRetweetCount());
                    $resultado->setText($mm->getText());
                    $resultado->setScreen_name($mm->getScreenName());
                    $resultado->setLink_perfil('http://www.twitter.com/'.$mm->getScreenName());
                    $resultado->setLink_tweet('http://www.twitter.com/'.$mm->getScreenName().'/status/'.$mm->getId_str_tweet());
                    
                    array_push($subarray, $resultado);
                }

                array_push($listagemTweets,$subarray);        
            }

            $json = json_encode($listagemTweets);
            
            $fp = fopen('arquivo.json', 'w');
            fwrite($fp, $json);
            
            fclose($fp);
        ?>       
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container">
              <div class="navbar-header">
                <a class="navbar-brand" href="javascript:window.history.go(-1)">Página Inicial</a>
              </div>
            </div>
        </nav>
        <div class="jumbotron">
            <div class="container">
              <p>Para a lista dos usuários que mais mencionarem o usuário da Locaweb,os tweets devem ser agregados por usuário, aplicando os mesmos critérios de ordenação dos mais relevantes.</p>
              <p><a class="btn btn-primary btn-lg" href="arquivo.json" target="_blank" role="button">Visualizar JSON &raquo;</a></p>
            </div>
        </div>
        <div class="container">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#classif" aria-controls="classif" role="tab" data-toggle="tab"><b>Classificação dos usuários</b></a></li>
          <li role="presentation"><a href="#tweets" aria-controls="tweets" role="tab" data-toggle="tab"><b>Tweets dos Usuários</b></a></li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
          <div role="tabpanel" class="tab-pane active" id="classif">
              <div class="panel panel-default">
                <table class="table">
                    <ul class="list-group">
                        <?php
                            foreach ($listagemTweets as $lt){
                                echo '<li class="list-group-item">&raquo; '.$lt[0]->getScreen_name().'</li>';
                            }
                        ?>
                    </ul>
                </table>
              </div>
          </div>
          <div role="tabpanel" class="tab-pane" id="tweets">
              <table class="table">
                <tr>
                    <th width="7%">Screen Name</th>
                    <th width="30%">Conteúdo</th> 
                    <th width="5%">Seguidores</th> 
                    <th width="5%">Retweets</th>
                    <th width="5%">Likes</th>
                    <th width="20%">Data/Hora do tweet</th>
                </tr>
                <?php
                    foreach ($listagemTweets as $lt){
                        echo '<tr>';
                        $rows = count($lt);
                        echo '<td rowspan="'.$rows.'"><a href="'.$lt[0]->getLink_perfil().'" target="_blank">@'.$lt[0]->getScreen_name().'</a></td>';
                        foreach ($lt as $r){
                            echo '<td>'.$r->getText().'</td>';
                            echo '<td>'.$r->getFollowers_count().'</td>';
                            echo '<td>'.$r->getRetweet_count().'</td>';
                            echo '<td>'.$r->getFavourites_count().'</td>';
                            echo '<td><a href="'.$r->getLink_tweet().'" target="_blank">'.$r->getCreated_at().'</a></td>';
                            echo '</tr>';
                        }
                        echo '</tr>';
                    }
                ?>
            </table>               
          </div>
        </div>
      </div>
        
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>

    </body>
</html>