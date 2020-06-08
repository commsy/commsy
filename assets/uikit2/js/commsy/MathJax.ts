'use strict';

export class MathJax {
    public bootstrap() {
        // @ts-ignore
        window.MathJax = {
            chtml: {
                fontURL: '/build/mathjax/fonts/'
            }
        };
    }
}