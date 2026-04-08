<?php

namespace Src\Content\Infrastructure\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Comment;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $articles = Article::all();
        $users = User::all();

        if ($users->isEmpty() || $articles->isEmpty()) {
            return;
        }

        // Comentários com erros de português para testar correção com IA
        $commentsWithErrors = [
            // Erros de acentuação
            'Muito bom o artigo, aprendi bastante sobre o assunto. Parabens pelo conteudo!',
            'Otimo conteudo!! Vcs sempre publicam coisas uteis aqui',
            'nao sabia dessa informaçao, muito obrigado por compartilhar!',
            'Esse blog é referencia no assunto, sempre volto aqui pra tirar duvidas',
            'Vou seguir essas dicas quando for comprar meu proximo veiculo com certesa',
            'A manutençao preventiva é muito importante, concordo com o artigo',

            // Abreviações e linguagem informal
            'nao concordo com tudo mas acho q faz sentido a maior parte',
            'Exelente post! Vou compartilhar com meus amigos q estao procurando carro',
            'sera que funciona msm? alguem ja testou isso ai?',
            'to procurando um carro usado a um tempo e esse artigo ajudou mto obrigado',
            'Acho que voces deveriam falar mais sobre financiamento, é um assunto importante pra qm quer comprar',
            'interessante, mas poderia ter mais detalhes tecnicos ne',
            'podiam fazer videos tambem ne? ia ser legal ver na pratica',
            'Poderiam fazer um artigo sobre como negociar preço? seria muito util pra nos',
            'ja li varios artigos sobre isso mas esse aqui ta bem completo parabens',
            'meu vizinho comprou um carro seguindo essas dicas e deu super certo kkk',

            // Erros de concordância e ortografia
            'Os carros usados é uma boa opção pra quem não tem muito dinheiro',
            'Eu ja comprei 3 carros e nunca tinha visto essas dicas antes',
            'Esse tipo de informação deveria ser mais divulgado, muita gente não sabe',
            'A revisao do carro é algo que muitos ignora mas é essencial',
            'Tava pesquisando sobre isso a dias e finalmente achei um artigo bom',
            'Minha esposa e eu estamos querendo trocar de carro e esse artigo veio na hora certa',

            // Erros de pontuação e maiúsculas
            'muito bom mesmo parabéns pelo trabalho',
            'ADOREI O ARTIGO VOU RECOMENDAR PRA TODO MUNDO',
            'legal...mas podia ter mais fotos dos carros ne',
            'interessante,gostei bastante,vou voltar mais vezes',

            // Mistura de erros
            'nossa q artigo top!! aprendi mto sobre financiamento de veiculos',
            'vcs sabem se isso funciona pra motos tbm? to querendo comprar uma',
            'show de bola esse conteudo, ja salvei nos favoritos pra ler dnv depois',
            'nunca tinha parado pra pensar nisso, faz mto sentido oq vcs falam',
            'alguem sabe onde posso encontrar mais informaçoes sobre ipva? o artigo nao fala sobre isso',
        ];

        foreach ($articles as $article) {
            // Todos os artigos terão comentários para facilitar teste
            $commentCount = rand(2, 5);
            $selectedComments = collect($commentsWithErrors)->random($commentCount);

            foreach ($selectedComments as $content) {
                Comment::create([
                    'article_id' => $article->id,
                    'user_id' => $users->random()->id,
                    'content' => $content,
                    'is_approved' => true,
                    'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }
    }
}
