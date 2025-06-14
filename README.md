# Financeiro 360° – Assistente de Investimentos Inteligente

Um poderoso sistema web para gestão de investimentos multiplataforma com automação em criptomoedas, análise de fundos imobiliários e otimização de carteira.

## 🚀 Funcionalidades Principais

### 1. Módulo Cripto - Automação Completa
- Conexão com Exchanges: Binance, KuCoin e Mercado Bitcoin via API REST/WebSocket
- Estratégias de Trading:
  - Cross de Médias Móveis (SMA/EMA)
  - RSI e MACD com alertas personalizáveis
  - Grid Trading para range markets
- Backtesting: Simulação histórica com dados de candle

### 2. Assistente Inteligente de Investimentos
- Recomendações Personalizadas:
  - FIIs com DY acima da média do setor
  - Ações com valuation atrativo
  - Alertas de eventos corporativos
- Perfil do Investidor: Questionário adaptativo

### 3. Otimizador de Carteira
- Análise de Risco: Diversificação setorial
- Rebalanceamento: Sugestões automáticas
- Relatórios Detalhados: PDF gerado com DomPDF

### 4. Dashboard Interativo
- Visualização em Tempo Real:
  - Gráficos interativos com Chart.js
  - Heatmap de setores
- Notificações Multiplataforma:
  - Telegram Bot
  - WebPush e e-mail

## 🛠 Stack Técnica

### Backend
- Laravel 10 + Octane (Swoole)
- PHP Trader Extension
- Laravel Queues (Redis)
- Laravel Excel
- Rubix ML

### Frontend
- Livewire 3 + Alpine.js
- Chart.js
- TailwindCSS

### Banco de Dados
- MySQL 8
- Redis

## 📋 Requisitos

- PHP 8.2+
- Composer 2+
- Node.js 18+
- MySQL 8+
- Redis 6+

## 🚀 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/financeiro360.git
cd financeiro360
```

2. Instale as dependências do PHP:
```bash
composer install
```

3. Instale as dependências do Node.js:
```bash
npm install
```

4. Configure o ambiente:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure o banco de dados no arquivo `.env`

6. Execute as migrações:
```bash
php artisan migrate
```

7. Inicie o servidor de desenvolvimento:
```bash
php artisan serve
npm run dev
```

## 🔧 Configuração

1. Configure as APIs das exchanges no arquivo `.env`:
```
BINANCE_API_KEY=seu_api_key
BINANCE_API_SECRET=seu_api_secret
KUCOIN_API_KEY=seu_api_key
KUCOIN_API_SECRET=seu_api_secret
MERCADO_BITCOIN_API_KEY=seu_api_key
MERCADO_BITCOIN_API_SECRET=seu_api_secret
```

2. Configure o bot do Telegram:
```
TELEGRAM_BOT_TOKEN=seu_token
```

## 📝 Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📧 Suporte

Para suporte, envie um email para seu-email@exemplo.com ou abra uma issue no GitHub.
