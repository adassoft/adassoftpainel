# Arquitetura do Sistema: EduSchedule (SaaS de Horário Escolar)

## 1. Visão Geral
O **EduSchedule** é uma plataforma SaaS especializada na gestão e automação de horários escolares, focada em escolas de ensino fundamental e médio.

### Estratégia de Negócio
- **Plataforma Central:** AdasSoft (Gestão de Clientes, Cobrança, Licenciamento).
- **Produto Satélite:** EduSchedule (Focado 100% na dor do cliente: montar horários).

## 2. Tecnologias
- **Framework Principal:** Laravel 11.
- **Painel Administrativo (Escola):** FilamentPHP v3.
- **Interface Visual (Grade):** React (integrado via Filament Custom Page ou Livewire + Alpine.js avançado).
- **Banco de Dados:** MySQL.
- **Integração:** AdasSoft API (validação de licença).

## 3. Fluxo de Usuário
1.  **Compra:** Cliente compra assinatura no site/painel AdasSoft.
2.  **Acesso:** Cliente recebe credenciais (ou link de ativação) para o EduSchedule.
3.  **Login:** EduSchedule valida credenciais e consulta status da licença no AdasSoft via API.
4.  **Onboarding:** Assistente de IA ajuda a cadastrar os primeiros dados (Turmas, Profs).
5.  **Operação:** Diretor usa a interface Drag & Drop para montar a grade.

## 4. Estrutura de Dados (Core)
*   **Institutions:** Escolas (Tenants).
*   **AcademicYears:** Anos letivos (2025, 2026).
*   **Employees:** Professores/Funcionários.
*   **TimeSlots:** Horários das aulas (ex: 07:30 - 08:20).
*   **Subjects:** Disciplinas (Matemática, História).
*   **Classes:** Turmas (6º A, 9º B).
*   **TeachingAssignments:** Vínculo (Quem dá aula do que, para quem).
*   **Schedules:** A grade em si (Grade final).
*   **Constraints:** Restrições (Proibições de horários).

## 5. Diferenciais de IA
*   **Auto-Solver:** Algoritmo que preenche a grade vazia.
*   **Conflict-Detector:** Detecta janelas, aulas duplas proibidas, professor em duas salas.
*   **Optimization:** Sugere trocas para melhorar a qualidade de vida do professor.
