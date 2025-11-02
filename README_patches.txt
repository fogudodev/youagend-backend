Patches gerados automaticamente
- Diretório de saída: /mnt/data/booking_patches
- Arquivos PHP verificados: 23
- Arquivos com mudanças automáticas (heurísticas): 2
  -> listagem: ['getDashboardData.php', 'getProfessionals.php']
- Arquivos com avisos (revisão manual recomendada): 0
  -> primeiro 10 avisos: []

Instruções:
1. Copie .env.example para .env e preencha credenciais reais (NUNCA commit em repositório).
2. Substitua seu db.php pelo arquivo db.php.patched (faça backup primeiro).
3. Revise os arquivos *.fixed.php no diretório acima e aplique manualmente onde fizer sentido.
4. Teste em ambiente local antes de subir para produção.

Observações importantes:
- As correções automáticas foram aplicadas com heurísticas simples (substituições por regex). Elas NÃO substituem revisão manual.
- Para correções seguras, cada query deve ser avaliada quanto à lógica de placeholders (named ou positional) e tipagem.
