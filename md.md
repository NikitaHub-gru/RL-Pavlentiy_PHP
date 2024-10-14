Я хочу видеть следующее, мне нужно выполнить 3 http запроса к серверу, первый это авторизация и получение токена  https://dimmy-yammi-barnaul.iiko.it:443/resto/api/auth?login=rlabs&pass=a4f7d1b395053edf689325d6f5056ceba1ccf792 информация о этом токене должна храниться внутри сайта в локальной переменной что бы его можно было получить в другом запросе  https://dimmy-yammi-barnaul.iiko.it:443/resto/api/v2/reports/olap?&key={token_api}  и телом запроса 
{
            "reportType": "SALES",
            "groupByRowFields": [
                "Delivery.Number",
                "DishServicePrintTime",
                "OpenDate.Typed",
                "Delivery.ActualTime"
            ],
            "groupByColFields": [],
            "aggregateFields": [],
            "filters": {
                "OpenDate.Typed": {
                    "filterType": "DateRange",
                    "periodType": "CUSTOM",
                    "from": {dateFrom},
                    "to": {dateTo},
                    "includeLow": "true",
                    "includeHigh": "true"
                }
            }
        }
И последний запрос на закрытие токена     https://dimmy-yammi-barnaul.iiko.it:443/resto/api/logout?key=[token]

На странице должны быть поля для ввода даты с возможностью выбора сегодня, вчера и ручного ввода, вся информация должна быть отфильтрована и отображена на странице в виде таблицы с колонками если в ответе есть дубли то их нужно объеденить и взять наименьшее время все действия должны быть отображены в alert и ошибки тоже что бы я мог их происпектировать и исправлять
