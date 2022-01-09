**Orders API**
----
  Creates and Gets orders for delivery.

* **URL**

  api/integrations/orders_api

* **Authorization**

    Header : `Authorization: <ACCESS_TOKEN>`

**1. Get Order**

*  **Description**

   This method gets Order information using id as base parameter. Responce contains order object with order id, order status and ETA (Estimate time of arrival) in minutes. ETA based on status: if status is picked_up, ETA - time to deliver, if status is assigned, ETA - time to pick up.

* **Method:**

  `GET`
  
*  **URL Params**

   **Required:**
 
   `id=[string]`

* **Success Response:**
  
  * **Code:** 200 <br />
    **Content example:** 
   ```json
   {
        "status": "Order found",
        "order" : {
            "order_id" : 52,
            "order_status" : "picked_up",
            "ETA" : 18
        }
    }

 * ** Responce payload **

   * `status=[string]`
   * `order=[obj]`
     * `order_id=[int]`
     * `order_status=[ new | picked_up | delivered ]`
     * `ETA=[int]`
   
* **Sample Call:**

   https://dev.rideon.co/api/integrations/orders_api?id=52

**2. Create Orders**

*  **Description**

   This method provides interface to create one or many orders for delivery.

* **Method:**

  `POST`

* **Authorization**

    Header : `Authorization: <ACCESS_TOKEN>`

* **Additional headers**

    `Content-Type: application/json`
  
* **Data Params**

   **Required:**
 
   See json data sample call below

   **Optional:**

   `cutomer_data -> customer_email=[string]`
   `fulfillment_details -> delivery_fee=[float]`
   `fulfillment_details -> delivery_fee_currency=[string]`

* **Success Response:**
  
  * **Code:** 200 <br />
    **Content example:** 
     ```json
    {
       "status": "Successfully created",
        "orders" : [
            {
                "order_id" : 52,
            },
            {
                "order_id" : 21,
            },
        ]
    }

* **Sample call:**

   https://dev.rideon.co/api/integrations/orders_api

    **Content example:** 
    ```json
    {
        "orders": [{
            "restaurant_location": {
                "restaurant_address": "1/2 Queensport Rd S, Murarrie QLD 4172, Australia",
                "restaurant_latitude": "-27.464338",
                "restaurant_longitude": "153.104493",
                "restaurant_timezone": "Australia/Brisbane"
            },
            "cutomer_data": {
                "customer_first_name": "Konstantin",
                "customer_last_name": "N",
                "customer_phone_number": "+61044885050",
                "customer_email": "pardusurbanus@protonmail.com"
            },
            "fulfillment_details": {
                "delivery_fee": "10.75",
                "delivery_fee_currency": "AUD",
                "delivery_instructions": "Double ring bell and go away for 2 meters",
                "accepted_time": "2020-06-25T04:21:11Z",
                "fulfillment_time": "2020-06-25T04:51:11Z",
                "drop_address": "46 Meyrick St, Cannon Hill QLD 4170, Australia",
                "drop_latitude": "-27.471113",
                "drop_longitude": "153.094153"
            }
        }]
    }