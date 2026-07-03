import os, requests
from telegram import Update
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, ContextTypes, filters, ConversationHandler
TOKEN=os.environ.get('TELEGRAM_BOT_TOKEN')
SERVER_URL=os.environ.get('SERVER_URL','http://127.0.0.1:5000')
ASK_PROBLEMA, ASK_DATA, ASK_CONFERMA = range(3)
async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    await update.message.reply_text('Ciao! Sono il bot dello Studio Musitano. Scrivimi il motivo della visita.'); return ASK_PROBLEMA
async def ricevi_problema(update: Update, context: ContextTypes.DEFAULT_TYPE):
    context.user_data['problema']=update.message.text; await update.message.reply_text('Perfetto. Quando vorresti venire?'); return ASK_DATA
async def ricevi_data(update: Update, context: ContextTypes.DEFAULT_TYPE):
    context.user_data['data']=update.message.text; await update.message.reply_text(f"Confermi richiesta per {context.user_data['data']}? Scrivi sì oppure no."); return ASK_CONFERMA
async def conferma(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if update.message.text.lower().startswith('s'):
        payload={'nome':update.message.from_user.first_name,'email':'telegram@user.com','data':context.user_data.get('data'),'messaggio':context.user_data.get('problema')}
        try:
            r=requests.post(f'{SERVER_URL}/api/prenota', json=payload, timeout=5)
            await update.message.reply_text('✅ Richiesta registrata. Lo studio ti ricontatterà per conferma.' if r.ok else f'Errore: {r.text}')
        except Exception as e: await update.message.reply_text(f'Connessione server fallita: {e}')
    else: await update.message.reply_text('Richiesta annullata.')
    return ConversationHandler.END
def main():
    if not TOKEN: raise RuntimeError('Manca TELEGRAM_BOT_TOKEN')
    app=ApplicationBuilder().token(TOKEN).build(); conv=ConversationHandler(entry_points=[CommandHandler('start', start)], states={ASK_PROBLEMA:[MessageHandler(filters.TEXT & ~filters.COMMAND,ricevi_problema)],ASK_DATA:[MessageHandler(filters.TEXT & ~filters.COMMAND,ricevi_data)],ASK_CONFERMA:[MessageHandler(filters.TEXT & ~filters.COMMAND,conferma)]}, fallbacks=[])
    app.add_handler(conv); print('Bot Telegram avviato'); app.run_polling()
if __name__=='__main__': main()
