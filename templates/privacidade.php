<?php
/**
 * templates/privacidade.php — Política de Privacidade e Cookies
 * Carlesso & Carlesso Advogados Associados
 * Exibida na rota /privacidade via index.php
 */
if (!defined('CARLESSO_CMS')) exit;
$email   = getConfig('email_contato', 'contato@carlessoecarlesso.adv.br');
$year    = date('Y');
$updated = '01 de maio de 2025';
?>

<div class="privacidade-content container" style="max-width:820px; padding: 48px 24px 80px;">

  <p class="privacidade-updated" style="color:var(--c-text-muted);font-size:.85rem;margin-bottom:32px;">
    Última atualização: <?= $updated ?>
  </p>

  <h2>1. Quem somos</h2>
  <p>
    <strong>Carlesso &amp; Carlesso Advogados Associados</strong>, inscrito sob o CNPJ 43.260.758/0001-71, é o controlador dos dados pessoais coletados neste site, nos termos da Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD).
  </p>
  <p>
    Contato do responsável pelo tratamento de dados:<br>
    <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
  </p>

  <h2>2. Quais dados coletamos e por quê</h2>
  <p>Coletamos dados nas seguintes situações:</p>

  <h3>a) Formulário de Contato</h3>
  <p>
    Quando você envia uma mensagem pelo formulário, coletamos nome, e-mail, telefone e o conteúdo da mensagem.
    Finalidade: responder à solicitação. Base legal: Art. 7º, V — execução de contrato ou procedimentos preliminares.
    Os dados são armazenados com segurança e não são compartilhados com terceiros.
  </p>

  <h3>b) Cookies Necessários</h3>
  <p>
    Cookies técnicos de sessão e proteção CSRF, indispensáveis ao funcionamento seguro do site. Não coletam dados pessoais identificáveis.
    Base legal: Art. 7º, II — legítimo interesse operacional.
  </p>

  <h3>c) Cookies de Análise (Google Analytics 4)</h3>
  <p>
    Com seu consentimento, utilizamos o Google Analytics 4 (GA4) para coletar dados anônimos de navegação (páginas visitadas, duração da sessão, origem do acesso). O IP é anonimizado e nenhuma informação é vinculada à sua identidade.
    Base legal: Art. 7º, I — consentimento do titular.
  </p>

  <h2>3. Cookies utilizados</h2>
  <div style="overflow-x:auto;margin-bottom:1.5rem;">
    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
      <thead>
        <tr style="background:var(--c-navy);color:#fff;">
          <th style="padding:10px 14px;text-align:left;">Nome</th>
          <th style="padding:10px 14px;text-align:left;">Categoria</th>
          <th style="padding:10px 14px;text-align:left;">Finalidade</th>
          <th style="padding:10px 14px;text-align:left;">Duração</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:10px 14px;font-family:monospace;">carlesso_lgpd</td>
          <td style="padding:10px 14px;">Necessário</td>
          <td style="padding:10px 14px;">Armazena suas preferências de cookies</td>
          <td style="padding:10px 14px;">12 meses</td>
        </tr>
        <tr style="border-bottom:1px solid #eee;background:#f9fafb;">
          <td style="padding:10px 14px;font-family:monospace;">PHPSESSID</td>
          <td style="padding:10px 14px;">Necessário</td>
          <td style="padding:10px 14px;">Sessão PHP (CSRF, formulários)</td>
          <td style="padding:10px 14px;">Sessão</td>
        </tr>
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:10px 14px;font-family:monospace;">_ga, _ga_*</td>
          <td style="padding:10px 14px;">Análise</td>
          <td style="padding:10px 14px;">Google Analytics — identificador anônimo de sessão</td>
          <td style="padding:10px 14px;">2 anos / 13 meses</td>
        </tr>
      </tbody>
    </table>
  </div>

  <h2>4. Compartilhamento de dados</h2>
  <p>
    Não vendemos, alugamos nem compartilhamos dados pessoais com terceiros para fins comerciais ou publicitários.
    Os dados de análise são processados pelo Google LLC sob os termos da
    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Política de Privacidade do Google</a>.
  </p>

  <h2>5. Seus direitos como titular (Art. 18 da LGPD)</h2>
  <p>Você tem direito a:</p>
  <ul style="padding-left:20px;line-height:2;">
    <li><strong>Confirmação</strong> do tratamento de dados</li>
    <li><strong>Acesso</strong> aos dados que tratamos sobre você</li>
    <li><strong>Correção</strong> de dados incompletos, inexatos ou desatualizados</li>
    <li><strong>Anonimização, bloqueio ou eliminação</strong> de dados desnecessários ou excessivos</li>
    <li><strong>Portabilidade</strong> dos seus dados a outro fornecedor de serviço</li>
    <li><strong>Eliminação</strong> dos dados tratados com base em consentimento</li>
    <li><strong>Revogação do consentimento</strong> a qualquer momento, sem prejuízo da legalidade do tratamento anterior</li>
    <li><strong>Oposição</strong> ao tratamento em caso de descumprimento da LGPD</li>
  </ul>
  <p>
    Para exercer seus direitos, entre em contato: <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>.<br>
    Responderemos em até <strong>15 dias úteis</strong>.
  </p>

  <h2>6. Segurança</h2>
  <p>
    Adotamos medidas técnicas e organizacionais adequadas para proteger seus dados contra acesso não autorizado, perda ou divulgação: HTTPS em todas as comunicações, tokens CSRF em formulários, senhas com hash e acesso restrito ao painel administrativo.
  </p>

  <h2>7. Retenção de dados</h2>
  <p>
    Dados de formulários de contato são mantidos pelo período necessário ao atendimento da solicitação ou pelo prazo legal aplicável. Dados de analytics são retidos pelo Google conforme sua política (padrão: 14 meses). Você pode solicitar exclusão antecipada a qualquer momento.
  </p>

  <h2>8. Alterações nesta Política</h2>
  <p>
    Esta política pode ser atualizada periodicamente. A data de revisão é indicada no topo desta página. Em caso de alterações relevantes, publicaremos aviso em destaque no site.
  </p>

  <h2>9. Contato e Encarregado (DPO)</h2>
  <p>
    Nosso ponto de contato para questões de privacidade e proteção de dados:<br>
    <strong>Carlesso &amp; Carlesso Advogados Associados</strong><br>
    <?= e(getConfig('endereco', 'R. Duque de Caxias, 1413 – Sala 301, Centro, São Miguel do Oeste – SC')) ?><br>
    <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
  </p>

  <div style="margin-top:40px;padding:16px 20px;background:var(--c-gray-light);border-radius:8px;font-size:.85rem;color:var(--c-text-muted);">
    <strong>Gerenciar suas preferências de cookies:</strong>
    Você pode alterar ou revogar seu consentimento a qualquer momento clicando no ícone de cookie
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin:0 2px;" aria-hidden="true">
      <path d="M21.598 11.064a1 1 0 0 0-.854-.172A2.938 2.938 0 0 1 20 11c-1.654 0-3-1.346-3.003-2.937.005-.034.016-.136.017-.17a1 1 0 0 0-1.297-1.034 2.962 2.962 0 0 1-3.587-2.744C12.13 3.547 11.8 3 11.064 3A9.015 9.015 0 0 0 3 11c0 4.963 4.037 9 9 9s9-4.037 9-9c0-.242-.016-.482-.042-.72a1 1 0 0 0-.36-.216zM12 18c-3.859 0-7-3.14-7-7a7.01 7.01 0 0 1 5.567-6.86A4.956 4.956 0 0 0 15 8c.08 0 .158-.005.237-.009A4.978 4.978 0 0 0 19.95 12.3 7.007 7.007 0 0 1 12 18z"/>
      <circle cx="9" cy="13" r="1.25"/><circle cx="11.5" cy="16.5" r="1.25"/><circle cx="14.5" cy="13.5" r="1.5"/>
    </svg>
    no canto inferior esquerdo da tela.
  </div>

</div>
