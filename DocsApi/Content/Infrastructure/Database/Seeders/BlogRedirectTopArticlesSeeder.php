<?php

namespace Src\Content\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class BlogRedirectTopArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $dicas = Category::firstOrCreate(['slug' => 'dicas'], [
            'name' => 'Dicas',
            'description' => 'Dicas práticas para compra, venda e negociação de veículos',
            'order' => 1,
            'is_active' => true,
        ]);

        $content = '
<p>Vender um veículo já exige atenção por si só. Quando entra uma procuração no meio, a maioria das dúvidas aparece no mesmo ponto: "isso é válido mesmo?" e "como eu faço sem correr risco?". A resposta curta é: sim, funciona, mas só quando o documento é bem feito, com poderes específicos e com os dados corretos.</p>

<p>Na prática, a procuração para transferência de veículo é um instrumento para autorizar outra pessoa a agir em seu nome no cartório e no Detran. Ela é comum quando o proprietário está viajando, mora em outra cidade, tem rotina apertada ou quando uma loja/despachante conduz o processo.</p>

<h2>Quando vale usar procuração</h2>
<p>Ela faz sentido quando há um motivo real para representação. Exemplos do dia a dia:</p>
<ul>
    <li>O vendedor não consegue comparecer para assinar ou reconhecer firma.</li>
    <li>O comprador fechou negócio a distância e precisa de alguém local para finalizar etapas.</li>
    <li>A transferência será conduzida por um despachante autorizado.</li>
    <li>Pessoa jurídica delega o processo para colaborador específico.</li>
</ul>

<p>Se as duas partes conseguem comparecer presencialmente sem dificuldade, o caminho direto normalmente é mais simples. A procuração entra para resolver exceção, não para complicar o processo.</p>

<h2>Procuração pública x procuração particular</h2>
<h3>Procuração pública</h3>
<p>É lavrada em cartório (escritura pública), com identificação formal das partes. Costuma ter maior aceitação e menos questionamento no atendimento, especialmente quando envolve estados diferentes.</p>

<h3>Procuração particular</h3>
<p>É redigida pelas partes e assinada com reconhecimento de firma quando exigido. Pode ser aceita, mas as regras variam conforme o estado e o procedimento. Por isso, antes de assinar, confirme a exigência do Detran local.</p>

<h2>O que não pode faltar no texto da procuração</h2>
<p>Esse é o trecho mais importante. Procuração vaga é convite para dor de cabeça. O ideal é trazer poderes objetivos, ligados apenas ao que precisa ser feito:</p>
<ul>
    <li>Identificação completa do outorgante e do procurador (nome, CPF/CNPJ, RG quando aplicável).</li>
    <li>Dados exatos do veículo: placa, Renavam, chassi e modelo.</li>
    <li>Poderes específicos: assinar ATPV-e/recibos, protocolar transferência, retirar documento, acompanhar exigências.</li>
    <li>Prazo de validade da procuração.</li>
    <li>Local e data de emissão.</li>
</ul>

<p>Regra prática: quanto mais claro o escopo, menor o risco. Evite "poderes gerais e irrestritos" se o objetivo é apenas transferir um único veículo.</p>

<h2>Checklist de documentos antes de iniciar</h2>
<ol>
    <li>Documento de identificação e CPF das partes envolvidas.</li>
    <li>Comprovante de endereço atualizado (quando solicitado).</li>
    <li>Documento do veículo e dados para ATPV-e.</li>
    <li>Certidões/consultas de débitos, multas e restrições.</li>
    <li>Procuração com poderes e prazo revisados.</li>
</ol>

<h2>Passo a passo para reduzir erro operacional</h2>
<h3>1. Conferência prévia de pendências</h3>
<p>Antes de qualquer assinatura, consulte débitos, bloqueios e restrições administrativas. A transferência trava se existir impeditivo ativo.</p>

<h3>2. Definição de escopo</h3>
<p>Descreva exatamente o que o procurador poderá fazer. Se houver necessidade de segunda via, vistoria ou retirada de documento, inclua esses poderes no texto.</p>

<h3>3. Formalização da procuração</h3>
<p>Escolha o tipo adequado (pública ou particular), com firma reconhecida quando exigido, e mantenha cópia assinada com todas as páginas.</p>

<h3>4. Execução da transferência</h3>
<p>O procurador apresenta os documentos, cumpre exigências, acompanha prazos e finaliza a transferência no órgão competente.</p>

<h3>5. Encerramento e guarda</h3>
<p>Concluído o processo, guarde comprovantes e considere revogar a procuração se ela não tiver mais utilidade.</p>

<h2>Erros comuns que custam tempo e dinheiro</h2>
<ul>
    <li>Placa ou Renavam com um dígito incorreto no texto da procuração.</li>
    <li>Poder para "vender", mas sem previsão para "assinar ATPV-e".</li>
    <li>Documento sem prazo, gerando interpretação divergente no atendimento.</li>
    <li>Falta de conferência de multas e taxas antes de protocolar.</li>
    <li>Delegação para pessoa sem experiência mínima no trâmite.</li>
</ul>

<h2>Como proteger vendedor e comprador</h2>
<p>Do lado do vendedor, a proteção está em limitar poderes e registrar tudo. Do lado do comprador, a segurança vem da verificação documental completa e da rastreabilidade das etapas.</p>

<p>Uma prática simples que ajuda muito: registrar por escrito cada entrega de documento, com data e assinatura. Isso reduz discussão futura e facilita auditoria do processo.</p>

<h2>Perguntas frequentes</h2>
<h3>A procuração substitui todos os documentos da transferência?</h3>
<p>Não. Ela autoriza representação. Os demais documentos do veículo e das partes continuam obrigatórios.</p>

<h3>Posso deixar procuração sem prazo?</h3>
<p>Tecnicamente pode existir, mas não é recomendável. Prazo definido reduz risco de uso indevido após o fim do negócio.</p>

<h3>Vale para qualquer estado?</h3>
<p>A base legal é nacional, mas o rito operacional muda por estado. Sempre confirme exigências locais antes de emitir.</p>

<h2>Conclusão</h2>
<p>A procuração para transferência de veículo é uma ferramenta útil quando existe necessidade real de representação. O segredo para funcionar sem susto é simples: escopo claro, dados corretos, prazo definido e conferência de pendências antes do protocolo. Com esse roteiro, o processo fica previsível e muito mais seguro para todos os envolvidos.</p>
        ';

        Article::updateOrCreate(
            ['slug' => 'como-funciona-a-procuracao-para-transferencia-de-veiculo'],
            [
                'category_id' => $dicas->id,
                'title' => 'Como Funciona a Procuração para Transferência de Veículo',
                'excerpt' => 'Guia prático e detalhado sobre procuração para transferência de veículo: quando usar, como redigir, quais documentos reunir e quais erros evitar no processo.',
                'content' => trim($content),
                'featured_image' => null,
                'author_name' => 'Equipe Editorial',
                'reading_time' => max(6, (int) ceil(str_word_count(strip_tags($content)) / 200)),
                'is_published' => true,
                'published_at' => now()->subDays(1),
                'meta' => [
                    'description' => 'Entenda como fazer procuração para transferência de veículo com segurança, incluindo checklist de documentos e principais erros a evitar.',
                    'keywords' => 'procuracao transferencia de veiculo, transferencia de carro, documentacao veicular, ATPV-e, Detran',
                ],
            ]
        );
    }
}
