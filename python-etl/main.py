from loguru import logger
from extract import extract_all
from transform import transform_all
from load import load_all, write_etl_log


def main():
    logger.info("ETL started")

    try:
        raw = extract_all()

        data = transform_all(raw)

        load_all(data)

        total_outbound = len(raw["outbound"])
        total_inbound = len(raw["inbound"])
        total_loaded = len(data["fact_calls"])

        write_etl_log(
            total_outbound=total_outbound,
            total_inbound=total_inbound,
            total_loaded=total_loaded,
            status="SUCCESS",
            message="ETL completed successfully"
        )

        logger.info("ETL finished successfully")
        logger.info(data["kpis"])

    except Exception as e:
        logger.exception("ETL failed")
        write_etl_log(0, 0, 0, "FAILED", str(e))
        raise


if __name__ == "__main__":
    main()