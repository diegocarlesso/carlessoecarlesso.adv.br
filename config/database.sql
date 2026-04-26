-- ═══════════════════════════════════════════════════════════════════
-- CARLESSO & CARLESSO – CMS DATABASE SCHEMA
-- MySQL 5.7+ / MariaDB 10.3+
-- Charset: utf8mb4 (unicode_ci)
-- ═══════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Usuários ──────────────────────────────────────────────────────
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) DEFAULT NULL,
  `role`       ENUM('admin','editor','author') NOT NULL DEFAULT 'editor',
  `last_login` TIMESTAMP    NULL DEFAULT NULL,
  `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Páginas dinâmicas ─────────────────────────────────────────────
DROP TABLE IF EXISTS `paginas`;
CREATE TABLE `paginas` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `titulo`           VARCHAR(200) NOT NULL,
  `slug`             VARCHAR(120) NOT NULL,
  `conteudo`         LONGTEXT     DEFAULT NULL,
  `blocos`           LONGTEXT     DEFAULT NULL,
  `meta_title`       VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `status`           ENUM('publicado','rascunho') NOT NULL DEFAULT 'rascunho',
  `show_in_menu`     TINYINT(1)   NOT NULL DEFAULT 0,
  `menu_order`       INT(11)      NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_status`    (`status`),
  KEY `idx_menu`      (`show_in_menu`,`menu_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Conteúdos por seção (textos editáveis das páginas estáticas) ─
DROP TABLE IF EXISTS `conteudos`;
CREATE TABLE `conteudos` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `pagina`     VARCHAR(60)  NOT NULL,
  `secao`      VARCHAR(60)  NOT NULL,
  `titulo`     VARCHAR(200) DEFAULT NULL,
  `conteudo`   LONGTEXT     DEFAULT NULL,
  `imagem`     VARCHAR(255) DEFAULT NULL,
  `extra`      TEXT         DEFAULT NULL,
  `updated_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pagina_secao` (`pagina`,`secao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Postagens (Produções/Artigos) ─────────────────────────────────
DROP TABLE IF EXISTS `postagens`;
CREATE TABLE `postagens` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `titulo`           VARCHAR(200) NOT NULL,
  `slug`             VARCHAR(200) DEFAULT NULL,
  `conteudo`         LONGTEXT     DEFAULT NULL,
  `imagem`           VARCHAR(255) DEFAULT NULL,
  `resumo`           VARCHAR(500) DEFAULT NULL,
  `status`           ENUM('publicado','rascunho') NOT NULL DEFAULT 'rascunho',
  `data_publicacao`  TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_data` (`status`,`data_publicacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SEO por slug ──────────────────────────────────────────────────
DROP TABLE IF EXISTS `seo`;
CREATE TABLE `seo` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `pagina`           VARCHAR(120) NOT NULL,
  `meta_title`       VARCHAR(255) DEFAULT NULL,
  `meta_description` VARCHAR(500) DEFAULT NULL,
  `keywords`         VARCHAR(255) DEFAULT NULL,
  `updated_at`       TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pagina` (`pagina`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mídia (biblioteca) ────────────────────────────────────────────
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `filename`      VARCHAR(200) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `file_path`     VARCHAR(255) NOT NULL,
  `file_type`     VARCHAR(80)  DEFAULT NULL,
  `file_size`     INT(11)      DEFAULT 0,
  `created_at`    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Configurações gerais (chave/valor) ────────────────────────────
DROP TABLE IF EXISTS `configs`;
CREATE TABLE `configs` (
  `chave` VARCHAR(80)  NOT NULL,
  `valor` TEXT         DEFAULT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Customizações de aparência (cores, fontes, logo) ──────────────
DROP TABLE IF EXISTS `customizations`;
CREATE TABLE `customizations` (
  `setting_key`   VARCHAR(80) NOT NULL,
  `setting_value` TEXT        DEFAULT NULL,
  `setting_type`  VARCHAR(20) DEFAULT 'text',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mensagens de contato ──────────────────────────────────────────
DROP TABLE IF EXISTS `contatos`;
CREATE TABLE `contatos` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `nome`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `telefone`   VARCHAR(30)  DEFAULT NULL,
  `assunto`    VARCHAR(120) DEFAULT NULL,
  `mensagem`   TEXT         NOT NULL,
  `lido`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lido_data` (`lido`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════
-- SEEDS / CONTEÚDO INICIAL
-- ═══════════════════════════════════════════════════════════════════

-- ── Usuário admin padrão ──────────────────────────────────────────
-- Senha padrão: admin123  (TROCAR APÓS PRIMEIRO LOGIN)
-- Hash gerado por password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `usuarios` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@carlessoecarlesso.adv.br',
 '$2y$10$8KqyzYvJv6kY6.DQg4mZcOmqe1vXY3BhUmKzZLQaTGJgxvBxKsLnq',
 'Administrador do Sistema', 'admin');

-- ── Páginas ──────────────────────────────────────────────────────
INSERT INTO `paginas` (`titulo`, `slug`, `meta_title`, `meta_description`, `status`, `show_in_menu`, `menu_order`) VALUES
('Início',             'inicio',      'Carlesso & Carlesso Advogados Associados',                                  'Escritório de advocacia em São Miguel do Oeste/SC. Atuação em Direito Penal, Previdenciário, Civil e do Trabalho.', 'publicado', 1, 1),
('Escritório',         'escritorio',  'Escritório | Carlesso & Carlesso Advogados Associados',                     'Conheça a história do escritório Carlesso & Carlesso, fundado em 2012 em São Miguel do Oeste/SC.',                  'publicado', 1, 2),
('Equipe',             'equipe',      'Equipe | Carlesso & Carlesso Advogados Associados',                         'Conheça os advogados associados e a equipe de apoio do escritório Carlesso & Carlesso.',                            'publicado', 1, 3),
('Nossos Fundamentos', 'fundamentos', 'Nossos Fundamentos | Carlesso & Carlesso Advogados Associados',             'Conheça os princípios, missão e visão que norteiam o trabalho do escritório Carlesso & Carlesso.',                  'publicado', 1, 4),
('Serviços Prestados', 'servicos',    'Serviços Prestados | Carlesso & Carlesso Advogados Associados',             'Atuação em Direito Penal, Direito Previdenciário, Direito Civil e Direito do Trabalho em São Miguel do Oeste/SC.',  'publicado', 1, 5),
('Produções',          'producoes',   'Produções | Carlesso & Carlesso Advogados Associados',                      'Artigos, conteúdos e produções jurídicas elaboradas pelo escritório Carlesso & Carlesso.',                          'publicado', 1, 6),
('Contato',            'contato',     'Contato | Carlesso & Carlesso Advogados Associados',                        'Entre em contato com o escritório Carlesso & Carlesso. R. Duque de Caxias, 1413, Sala 301 – São Miguel do Oeste/SC.','publicado', 1, 7);

-- ── Conteúdos institucionais por seção ───────────────────────────
INSERT INTO `conteudos` (`pagina`, `secao`, `titulo`, `conteudo`) VALUES
('inicio', 'banner_titulo',    'Excelência jurídica em São Miguel do Oeste',                  'Atuação sólida e personalizada nas áreas de Direito Penal, Previdenciário, Civil e do Trabalho.'),
('inicio', 'banner_subtitulo', 'CARLESSO & CARLESSO – ADVOGADOS ASSOCIADOS',                  'Atuação consolidada desde 2012'),
('inicio', 'banner_descricao', '',                                                            'Olá, que bom que você chegou até nós. Sabemos que a confiança em qualquer área de trabalho é construída a partir da história das pessoas que conduzem os processos. Nosso trabalho valoriza o respeito com cada pessoa que chega até nós – confiança, sigilo e zelo nos processos são fundamentos que priorizamos.'),
('inicio', 'sobre_titulo',     'Sobre o Escritório',                                          ''),
('inicio', 'sobre_descricao',  '',                                                            'Fundado em 1º de junho de 2012, o escritório Carlesso & Carlesso é uma sociedade de advogados que prima pela excelência, ética e compromisso com cada cliente. Em 24 de agosto de 2021 consolidou-se a atual estrutura, marca que representa, até hoje, a identidade e os valores do escritório.'),

('escritorio', 'historia',     'Nossa História',                                              '<p>Olá, que bom que você chegou até nós. Sabemos que a confiança em qualquer área de trabalho é construída a partir da história das pessoas que conduzem os processos, por isso, faremos nesta sessão, um recorte dos eventos que marcaram a nossa origem.</p><p>A história do escritório Carlesso e Carlesso Advogados Associados é datada em 1º de junho de 2012 e idealizada pelo sócio fundador, <strong>Jean Carlos Carlesso</strong>, em parceria com dois colegas de graduação. À época, a sociedade atuava sob a denominação – Carlesso e Minuscolli Advogados Associados.</p><p>Com o passar dos anos e em decorrência de reestruturações internas, aconteceu a saída dos então sócios, sendo que, em novembro de 2016, passou a integrar a sociedade o advogado <strong>Guilherme Carlesso</strong>, em conjunto com a Advogada Nelita Muller e Jhyonnattann C. Ganzer, dando origem ao escritório – Carlesso e Ganzer Advogados Associados.</p><p>Posteriormente, em meio às transformações impostas pelo cenário da pandemia, ocorreram novas mudanças societárias, culminando com a saída dos sócios Nelita Muller e Jhyonnattann C. Ganzer. Assim, em 24 de agosto de 2021, consolidou-se a atual estrutura sob a denominação – <strong>Carlesso e Carlesso Advogados Associados</strong>, marca que representa, até hoje, a identidade e os valores do escritório.</p><p>Nosso trabalho valoriza o respeito com cada pessoa que chega até nós. Confiança, sigilo e zelo nos processos, são fundamentos que priorizamos.</p>'),

('equipe', 'guilherme',        'Guilherme Carlesso',                                          'Guilherme Carlesso é Advogado, de São Miguel do Oeste/SC. Bacharel em Direito pela Universidade do Oeste de Santa Catarina – UNOESC e especialista em Advocacia Trabalhista pela Universidade Leonardo da Vinci.'),
('equipe', 'jean',             'Jean Carlos Carlesso',                                        'Jean Carlos Carlesso é Advogado, de São Miguel do Oeste/SC, formado em Direito pela Universidade do Oeste de Santa Catarina – UNOESC; Pós-Graduado em Direito Penal e Processual Penal pela Faculdade Damásio de Jesus e licenciado em Filosofia pelo Centro Universitário Internacional Uninter.'),
('equipe', 'apoio',            'Equipe de Apoio',                                             '<p><strong>Advogados:</strong> Higor Mateus Scain e Andréia Colle.</p><p><strong>Secretário:</strong> Jean Pedro Hemsing.</p>'),

('fundamentos', 'principios',  'Princípios',                                                  'Esta Sociedade (parceria) e as ações de seus associados, bem como a solução de eventuais dilemas, será norteada/regida pelos seguintes princípios: I – Sinceridade; II – Honestidade; III – Transparência; IV – Profissionalismo; V – Ética; VI – Sigilismo; VII – Equidade; VIII – Espírito de equipe; IX – Entusiasmo; X – Responsabilidade social.'),
('fundamentos', 'visao',       'Visão',                                                       'Buscar ser reconhecido como uma sociedade de excelência no mercado de prestação de serviços, zelando pela alta qualidade em todas as atividades jurídicas; como uma sociedade justa, fraterna e igualitária; que acredita no direito como a melhor forma de solucionar dissídios e promover a justiça.'),
('fundamentos', 'missao',      'Missão',                                                      '<p><strong>I –</strong> Atender e superar as expectativas de nossos clientes e parceiros, fornecendo serviços seguros e com qualidade diferenciada, através de modernos procedimentos, atuando com responsabilidade social e gerando valores para nossos clientes, parceiros, colaboradores e a sociedade.</p><p><strong>II –</strong> Continuamente expandir no mercado jurídico, com o compromisso de aperfeiçoamento de seus serviços prestados.</p><p><strong>III –</strong> Fomentar talentos; formar os melhores profissionais do mercado e investir continuamente em suas carreiras.</p><p><strong>IV –</strong> Preservar um bom ambiente de trabalho.</p>'),

('servicos', 'penal',           'Direito Penal',                                              'Atuação em todas as fases do processo penal, desde inquérito policial até instâncias superiores. Defesa técnica em ações criminais, com atendimento personalizado e estratégico para cada caso.'),
('servicos', 'previdenciario',  'Direito Previdenciário',                                     'Aposentadorias por idade, tempo de contribuição, especial e por invalidez. Auxílios, pensões, revisões de benefícios e planejamento previdenciário – tudo com análise técnica e atualização constante junto ao INSS.'),
('servicos', 'civil',           'Direito Civil',                                              'Contratos, responsabilidade civil, direito de família, sucessões, indenizações, direito do consumidor e demais demandas patrimoniais. Solução jurídica completa para questões civis e empresariais.'),
('servicos', 'trabalho',        'Direito do Trabalho',                                        'Atuação consultiva e contenciosa para empregados e empregadores. Reconhecimento de vínculo, verbas rescisórias, horas extras, equiparação salarial, acidentes de trabalho e demais demandas trabalhistas.'),

('producoes', 'introducao',     'Produções Acadêmicas e Profissionais',                       'Espaço dedicado às produções e trabalhos elaborados pelos integrantes do escritório. Conteúdo jurídico voltado à informação, atualização e contribuição com a comunidade.'),

('contato', 'introducao',       'Fale Conosco',                                               'Estamos à disposição para esclarecer dúvidas, agendar atendimentos e oferecer orientação jurídica especializada. Entre em contato pelos canais abaixo ou utilize o formulário ao lado.');

-- ── SEO por página ───────────────────────────────────────────────
INSERT INTO `seo` (`pagina`, `meta_title`, `meta_description`, `keywords`) VALUES
('inicio',      'Carlesso & Carlesso Advogados Associados | São Miguel do Oeste/SC',  'Escritório de advocacia em São Miguel do Oeste/SC com atuação em Direito Penal, Previdenciário, Civil e do Trabalho. Atendimento sigiloso e personalizado.', 'advocacia, advogado, são miguel do oeste, direito penal, direito civil, direito trabalho, direito previdenciário, OAB SC'),
('escritorio',  'O Escritório | Carlesso & Carlesso Advogados Associados',            'Fundado em 2012, o escritório Carlesso & Carlesso é referência em advocacia no oeste de Santa Catarina. Conheça nossa história.',                            'escritório advocacia, história, são miguel do oeste, advogados sócios'),
('equipe',      'Equipe de Advogados | Carlesso & Carlesso',                          'Advogados associados Guilherme Carlesso e Jean Carlos Carlesso, com equipe de apoio especializada em diversas áreas do Direito.',                            'advogados, equipe jurídica, são miguel do oeste, advocacia'),
('fundamentos', 'Princípios, Missão e Visão | Carlesso & Carlesso',                   'Conheça os princípios éticos, a missão e a visão que norteiam a atuação do escritório Carlesso & Carlesso Advogados Associados.',                            'princípios, missão, visão, valores, advocacia ética'),
('servicos',    'Serviços Jurídicos | Carlesso & Carlesso Advogados',                 'Atuação especializada em Direito Penal, Direito Previdenciário, Direito Civil e Direito do Trabalho. Atendimento jurídico em São Miguel do Oeste/SC.',       'serviços jurídicos, direito penal, direito civil, previdência, trabalho'),
('producoes',   'Produções e Artigos Jurídicos | Carlesso & Carlesso',                'Artigos, produções acadêmicas e conteúdos jurídicos elaborados pela equipe do escritório Carlesso & Carlesso.',                                              'artigos jurídicos, produção acadêmica, advocacia, direito'),
('contato',     'Contato | Carlesso & Carlesso Advogados Associados',                 'Entre em contato com o escritório Carlesso & Carlesso. R. Duque de Caxias, 1413, Sala 301, Centro – São Miguel do Oeste/SC. (49) 3621-2254.',                'contato advocacia, advogado são miguel do oeste, telefone, endereço');

-- ── Configurações gerais ─────────────────────────────────────────
INSERT INTO `configs` (`chave`, `valor`) VALUES
('site_titulo',    'Carlesso & Carlesso Advogados Associados'),
('site_subtitulo', 'Excelência jurídica em São Miguel do Oeste/SC'),
('telefone',       '(49) 3621-2254'),
('whatsapp',       '5549999999999'),
('email_contato',  'contato@carlessoecarlesso.adv.br'),
('endereco',       'R. Duque de Caxias, 1413 – Sala 301, Centro, São Miguel do Oeste – SC'),
('horario',        'Segunda a Sexta, das 8h às 18h'),
('instagram',      'https://www.instagram.com/carlessoecarlessoadv'),
('facebook',       '#'),
('linkedin',       '#'),
('mapa_lat',       '-26.7252'),
('mapa_lng',       '-53.5189');

-- ── Customizações de aparência ───────────────────────────────────
INSERT INTO `customizations` (`setting_key`, `setting_value`, `setting_type`) VALUES
('primary_color',   '#527095',                            'color'),
('secondary_color', '#1a3554',                            'color'),
('accent_color',    '#c8832a',                            'color'),
('text_color',      '#1c1c1c',                            'color'),
('heading_font',    "'Hepta Slab', 'Georgia', serif",     'font'),
('body_font',       "'Open Sans', 'Helvetica', sans-serif", 'font'),
('logo_text',       'Carlesso & Carlesso',                'text'),
('footer_text',     'Todos os direitos reservados.',      'text');
