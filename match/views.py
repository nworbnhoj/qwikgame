from django.shortcuts import render
from django.views import View


class MatchView(View):

    def get(self, request):
        return render(request, "match/match.html")
