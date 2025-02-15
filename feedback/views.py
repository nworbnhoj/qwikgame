import logging
from django.http import HttpResponse
from django.shortcuts import render
from feedback.models import Feedback
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class FeedbackListView(QwikView):
    template_name = 'feedback/feedback_list.html'

    def context(self, request, *args, **kwargs):
        feedbacks = Feedback.objects.all()
        kwargs['items'] = feedbacks
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('feedback')

        context = super().context(request, *args, **kwargs)
        context |= {
            'feedback': context.get('item'),
            'feedbacks': context.get('items'),
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        feedback = context.get('feedback')
        if not feedback: 
            return render(request, self.template_name, context)


class FeedbackView(FeedbackListView):
    template_name = 'feedback/feedback.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        pk = kwargs.get('feedback')
        if pk:
	        context |= {
	            'feedback': Feedback.objects.filter(pk=pk).first()
	        }
        self._context = context
        return self._context


    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        # feedback = context.get('feedback')
        # feedback.mark_seen([self.user.player.pk]).save()
        return render(request, self.template_name, context)

