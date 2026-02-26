from modeltranslation.translator import register, TranslationOptions
from .models import Game

@register(Game)
class NewsTranslationOptions(TranslationOptions):
    fields = ('name',)